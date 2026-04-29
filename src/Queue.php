<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use BackedEnum;
use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\AdapterPushHandler;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Push\SynchronousPushHandler;
use Yiisoft\Queue\Worker\WorkerInterface;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Provider\QueueProviderInterface;

final class Queue implements QueueInterface
{
    /**
     * @var array|array[]|callable[]|MiddlewarePushInterface[]|string[]
     */
    private array $middlewareDefinitions;

    /**
     * @var MessageHandlerPushInterface $finalPushHandler The final push handler in the middleware chain, responsible
     * for actually sending the message. Uses {@see SynchronousPushHandler} in synchronous mode or
     * {@see AdapterPushHandler} otherwise.
     */
    private MessageHandlerPushInterface $finalPushHandler;

    private string $name;

    public function __construct(
        private readonly WorkerInterface $worker,
        private readonly LoopInterface $loop,
        private readonly LoggerInterface $logger,
        private readonly PushMiddlewareDispatcher $pushMiddlewareDispatcher,
        private readonly ?AdapterInterface $adapter = null,
        string|BackedEnum $name = QueueProviderInterface::DEFAULT_QUEUE,
        MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions,
    ) {
        $this->name = StringNormalizer::normalize($name);
        $this->middlewareDefinitions = $middlewareDefinitions;
        $this->finalPushHandler = $this->isSynchronous()
            ? new SynchronousPushHandler($worker, $this)
            : new AdapterPushHandler($this->adapter);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $this->logger->debug(
            'Preparing to push message with message type "{messageType}".',
            ['messageType' => $message->getType()],
        );

        $message = $this->pushMiddlewareDispatcher->dispatch(
            $message,
            $this->createPushHandler(),
        );

        if ($this->isSynchronous()) {
            $this->logger->info(
                'Processed message with message type "{messageType}" synchronously.',
                ['messageType' => $message->getType()],
            );
            return $message;
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
        if ($this->isSynchronous()) {
            $this->logger->debug(
                'Queue is in synchronous mode (no adapter). Messages are processed on push. run() does nothing.',
            );
            return 0;
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
        if ($this->isSynchronous()) {
            $this->logger->info('Cannot listen without an adapter. Queue is in synchronous mode.');
            return;
        }

        $this->logger->info('Start listening to the queue.');
        $this->adapter->subscribe(fn(MessageInterface $message) => $this->handle($message));
        $this->logger->info('Finish listening to the queue.');
    }

    public function status(string|int $id): MessageStatus
    {
        if ($this->isSynchronous()) {
            return MessageStatus::NOT_FOUND;
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

    private function createPushHandler(): MessageHandlerPushInterface
    {
        return new class (
            $this->finalPushHandler,
            $this->pushMiddlewareDispatcher,
            $this->middlewareDefinitions,
        ) implements MessageHandlerPushInterface {
            public function __construct(
                /**
                 * @var MessageHandlerPushInterface $finishHandler Final handler invoked after all middlewares are
                 * processed.
                 */
                private readonly MessageHandlerPushInterface $finishHandler,
                private readonly PushMiddlewareDispatcher $dispatcher,
                /**
                 * @var array|array[]|callable[]|MiddlewarePushInterface[]|string[]
                 */
                private readonly array $middlewares,
            ) {}

            public function handlePush(MessageInterface $message): MessageInterface
            {
                return $this->dispatcher
                    ->withMiddlewares($this->middlewares)
                    ->dispatch($message, $this->finishHandler);
            }
        };
    }

    /**
     * @psalm-assert-if-false !null $this->adapter
     */
    private function isSynchronous(): bool
    {
        return $this->adapter === null;
    }
}
