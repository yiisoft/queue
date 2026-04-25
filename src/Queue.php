<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use BackedEnum;
use BadMethodCallException;
use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\AdapterPushHandler;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Worker\WorkerInterface;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Provider\QueueProviderInterface;

final class Queue implements QueueInterface
{
    /**
     * @var array|array[]|callable[]|MiddlewarePushInterface[]|string[]
     */
    private array $middlewareDefinitions;
    private AdapterPushHandler $adapterPushHandler;
    private string $name;

    public function __construct(
        private readonly WorkerInterface $worker,
        private readonly LoopInterface $loop,
        private readonly LoggerInterface $logger,
        private readonly PushMiddlewareDispatcher $pushMiddlewareDispatcher,
        string|BackedEnum $name = QueueProviderInterface::DEFAULT_QUEUE,
        private ?AdapterInterface $adapter = null,
        MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions,
    ) {
        $this->name = StringNormalizer::normalize($name);
        $this->middlewareDefinitions = $middlewareDefinitions;
        $this->adapterPushHandler = new AdapterPushHandler();

        if ($this->adapter === null) {
            $this->logger->warning(
                'Queue "{name}" has no adapter configured. Messages will be processed synchronously on push.'
                . ' Add an adapter for asynchronous processing in production.',
                ['name' => $this->name],
            );
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function push(
        MessageInterface $message,
        MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions,
    ): MessageInterface {
        $this->logger->debug(
            'Preparing to push message with message type "{messageType}".',
            ['messageType' => $message->getType()],
        );

        $request = new PushRequest($message, $this->adapter);
        $message = $this->pushMiddlewareDispatcher
            ->dispatch($request, $this->createPushHandler(...$middlewareDefinitions))
            ->getMessage();

        if ($this->adapter === null) {
            $this->logger->debug('No adapter configured. Processing message synchronously on push.');
            $this->worker->process($message, $this);
        }

        /** @var string $messageId */
        $messageId = $message->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? 'null';
        $this->logger->info(
            'Pushed message with message type "{messageType}" to the queue. Assigned ID #{id}.',
            ['messageType' => $message->getType(), 'id' => $messageId],
        );

        return $message;
    }

    public function run(int $max = 0): int
    {
        if ($this->adapter === null) {
            throw new BadMethodCallException(
                'Cannot run queue "' . $this->name . '": no adapter configured.'
                . ' Messages are processed synchronously on push when no adapter is set.',
            );
        }

        $this->logger->debug('Start processing queue messages.');
        $count = 0;

        $handlerCallback = function (MessageInterface $message) use (&$max, &$count): bool {
            if (($max > 0 && $max <= $count) || !$this->handle($message)) {
                return false;
            }
            $count++;

            return true;
        };

        $this->adapter->runExisting($handlerCallback);

        $this->logger->info(
            'Processed {count} queue messages.',
            ['count' => $count],
        );

        return $count;
    }

    public function listen(): void
    {
        if ($this->adapter === null) {
            throw new BadMethodCallException(
                'Cannot listen to queue "' . $this->name . '": no adapter configured.'
                . ' Messages are processed synchronously on push when no adapter is set.',
            );
        }

        $this->logger->info('Start listening to the queue.');
        $this->adapter->subscribe(fn(MessageInterface $message) => $this->handle($message));
        $this->logger->info('Finish listening to the queue.');
    }

    public function status(string|int $id): MessageStatus
    {
        if ($this->adapter === null) {
            throw new BadMethodCallException(
                'Cannot check message status in queue "' . $this->name . '": no adapter configured.'
                . ' Messages are processed synchronously on push when no adapter is set.',
            );
        }

        return $this->adapter->status($id);
    }

    public function withMiddlewares(MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->middlewareDefinitions = $middlewareDefinitions;

        return $instance;
    }

    public function withMiddlewaresAdded(MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->middlewareDefinitions = [...array_values($instance->middlewareDefinitions), ...array_values($middlewareDefinitions)];

        return $instance;
    }

    private function handle(MessageInterface $message): bool
    {
        $this->worker->process($message, $this);

        return $this->loop->canContinue();
    }

    private function createPushHandler(MiddlewarePushInterface|callable|array|string ...$middlewares): MessageHandlerPushInterface
    {
        return new class (
            $this->adapterPushHandler,
            $this->pushMiddlewareDispatcher,
            array_merge($this->middlewareDefinitions, $middlewares),
        ) implements MessageHandlerPushInterface {
            public function __construct(
                private readonly AdapterPushHandler $adapterPushHandler,
                private readonly PushMiddlewareDispatcher $dispatcher,
                /**
                 * @var array|array[]|callable[]|MiddlewarePushInterface[]|string[]
                 */
                private readonly array $middlewares,
            ) {}

            public function handlePush(PushRequest $request): PushRequest
            {
                return $this->dispatcher
                    ->withMiddlewares($this->middlewares)
                    ->dispatch($request, $this->adapterPushHandler);
            }
        };
    }
}
