<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Psr\Log\LoggerInterface;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\AdapterNotConfiguredException;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\Push\AdapterPushHandler;
use Yiisoft\Yii\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Middleware\Push\PushRequest;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

final class Queue implements QueueInterface
{
    /**
     * @var array|array[]|callable[]|string[]|MiddlewarePushInterface[]
     */
    private array $middlewareDefinitions;

    public function __construct(
        private WorkerInterface $worker,
        private LoopInterface $loop,
        private LoggerInterface $logger,
        private PushMiddlewareDispatcher $pushMiddlewareDispatcher,
        private AdapterPushHandler $adapterPushHandler,
        private ?AdapterInterface $adapter = null,
        private string $channelName = QueueFactoryInterface::DEFAULT_CHANNEL_NAME,
        MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions
    ) {
        $this->middlewareDefinitions = $middlewareDefinitions;
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

        $this->logger->info(
            'Pushed message with handler name "{handlerName}" to the queue. Assigned ID #{id}.',
            ['name' => $message->getHandlerName(), 'id' => $message->getId() ?? 'null']
        );

        return $message;
    }

    public function run(int $max = 0): void
    {
        $this->checkAdapter();

        $this->logger->debug('Start processing queue messages.');
        $count = 0;

        $callback = function (MessageInterface $message) use (&$max, &$count): bool {
            if (($max > 0 && $max <= $count) || !$this->loop->canContinue()) {
                return false;
            }

            $this->handle($message);
            $count++;

            return true;
        };

        /** @psalm-suppress PossiblyNullReference */
        $this->adapter->runExisting($callback);

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

    public function status(string $id): JobStatus
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
        $instance->middlewareDefinitions = [...$instance->middlewareDefinitions, ...$middlewareDefinitions];

        return $instance;
    }

    protected function handle(MessageInterface $message): void
    {
        $this->worker->process($message, $this);
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
            $middlewares
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
