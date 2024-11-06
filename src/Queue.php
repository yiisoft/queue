<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Exception\AdapterConfiguration\AdapterNotConfiguredException;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\AdapterPushHandler;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Worker\WorkerInterface;
use Yiisoft\Queue\Message\IdEnvelope;

final class Queue implements QueueInterface
{
    /** @psalm-suppress MissingClassConstType */
    public const DEFAULT_CHANNEL_NAME = 'yii-queue';

    /**
     * @var array|array[]|callable[]|MiddlewarePushInterface[]|string[]
     */
    private array $middlewareDefinitions;
    private AdapterPushHandler $adapterPushHandler;

    public function __construct(
        private WorkerInterface $worker,
        private LoopInterface $loop,
        private LoggerInterface $logger,
        private PushMiddlewareDispatcher $pushMiddlewareDispatcher,
        private ?AdapterInterface $adapter = null,
        private string $channelName = self::DEFAULT_CHANNEL_NAME,
        MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions
    ) {
        $this->middlewareDefinitions = $middlewareDefinitions;
        $this->adapterPushHandler = new AdapterPushHandler();
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }

    public function push(
        MessageInterface $message,
        MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions
    ): MessageInterface {
        $this->logger->debug(
            'Preparing to push message with handler name "{handlerName}".',
            ['handlerName' => $message->getHandlerName()]
        );

        $request = new PushRequest($message, $this->adapter);
        $message = $this->pushMiddlewareDispatcher
            ->dispatch($request, $this->createPushHandler($middlewareDefinitions))
            ->getMessage();

        $messageId = $message->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? 'null';
        $this->logger->info(
            'Pushed message with handler name "{handlerName}" to the queue. Assigned ID #{id}.',
            ['handlerName' => $message->getHandlerName(), 'id' => $messageId]
        );

        return $message;
    }

    public function run(int $max = 0): int
    {
        $this->checkAdapter();

        $this->logger->debug('Start processing queue messages.');
        $count = 0;

        $handlerCallback = function (MessageInterface $message) use (&$max, &$count): bool {
            if (($max > 0 && $max <= $count) || !$this->handle($message)) {
                return false;
            }
            $count++;

            return true;
        };

        /** @psalm-suppress PossiblyNullReference */
        $this->adapter->runExisting($handlerCallback);

        $this->logger->info(
            'Processed {count} queue messages.',
            ['count' => $count]
        );

        return $count;
    }

    public function listen(): void
    {
        $this->checkAdapter();

        $this->logger->info('Start listening to the queue.');
        /** @psalm-suppress PossiblyNullReference */
        $this->adapter->subscribe(fn (MessageInterface $message) => $this->handle($message));
        $this->logger->info('Finish listening to the queue.');
    }

    public function status(string|int $id): JobStatus
    {
        $this->checkAdapter();

        /** @psalm-suppress PossiblyNullReference */
        return $this->adapter->status($id);
    }

    public function withAdapter(AdapterInterface $adapter): self
    {
        $new = clone $this;
        $new->adapter = $adapter;

        return $new;
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

    public function withChannelName(string $channel): self
    {
        $instance = clone $this;
        $instance->channelName = $channel;

        return $instance;
    }

    private function handle(MessageInterface $message): bool
    {
        $this->worker->process($message, $this);

        return $this->loop->canContinue();
    }

    private function checkAdapter(): void
    {
        if ($this->adapter === null) {
            throw new AdapterNotConfiguredException();
        }
    }

    private function createPushHandler(array $middlewares): MessageHandlerPushInterface
    {
        return new class (
            $this->adapterPushHandler,
            $this->pushMiddlewareDispatcher,
            array_merge($this->middlewareDefinitions, $middlewares)
        ) implements MessageHandlerPushInterface {
            public function __construct(
                private AdapterPushHandler $adapterPushHandler,
                private PushMiddlewareDispatcher $dispatcher,
                private array $middlewares,
            ) {
            }

            public function handlePush(PushRequest $request): PushRequest
            {
                return $this->dispatcher
                    ->withMiddlewares($this->middlewares)
                    ->dispatch($request, $this->adapterPushHandler);
            }
        };
    }
}
