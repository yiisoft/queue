<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Exception\AdapterConfiguration\AdapterNotConfiguredException;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\AdapterHandler;
use Yiisoft\Queue\Middleware\MessageHandlerInterface;
use Yiisoft\Queue\Middleware\MiddlewareInterface;
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Request;
use Yiisoft\Queue\Worker\WorkerInterface;

final class Queue implements QueueInterface
{
    /**
     * @var array|array[]|callable[]|MiddlewareInterface[]|string[]
     */
    private array $middlewareDefinitions;
    private AdapterHandler $adapterHandler;

    public function __construct(
        private WorkerInterface $worker,
        private LoopInterface $loop,
        private LoggerInterface $logger,
        private MiddlewareDispatcher $pushMiddlewareDispatcher,
        private ?AdapterInterface $adapter = null,
        private string $channelName = QueueFactoryInterface::DEFAULT_CHANNEL_NAME,
        MiddlewareInterface|callable|array|string ...$middlewareDefinitions
    ) {
        $this->middlewareDefinitions = $middlewareDefinitions;
        $this->adapterHandler = new AdapterHandler();
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }

    public function push(
        MessageInterface $message,
        MiddlewareInterface|callable|array|string ...$middlewareDefinitions
    ): MessageInterface {
        $this->logger->debug(
            'Preparing to push message with data "{data}" and metadata: "{metadata}.',
            ['data' => $message->getData(), 'metadata' => json_encode($message->getMetadata())]
        );

        $request = new Request($message, $this);
        $message = $this->pushMiddlewareDispatcher
            ->dispatch($request, $this->createHandler($middlewareDefinitions))
            ->getMessage();

        $messageId = $message->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? 'null';
        $this->logger->info(
            'Pushed message id: "{id}".',
            ['id' => $messageId]
        );

        return $message;
    }

    public function run(int $max = 0): void
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

    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }

    public function withMiddlewares(MiddlewareInterface|callable|array|string ...$middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->middlewareDefinitions = $middlewareDefinitions;

        return $instance;
    }

    public function withMiddlewaresAdded(MiddlewareInterface|callable|array|string ...$middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->middlewareDefinitions = [
            ...array_values($instance->middlewareDefinitions),
            ...array_values($middlewareDefinitions),
        ];

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

    private function createHandler(array $middlewares): MessageHandlerInterface
    {
        return new class (
            $this->adapterHandler,
            $this->pushMiddlewareDispatcher,
            array_merge($this->middlewareDefinitions, $middlewares)
        ) implements MessageHandlerInterface {
            public function __construct(
                private AdapterHandler $adapterHandler,
                private MiddlewareDispatcher $dispatcher,
                private array $middlewares,
            ) {
            }

            public function handle(Request $request): Request
            {
                return $this->dispatcher
                    ->withMiddlewares($this->middlewares)
                    ->dispatch($request, $this->adapterHandler);
            }
        };
    }
}
