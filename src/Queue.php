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
     * @var array<array|callable|MiddlewarePushInterface|string> Queue-specific middleware definitions.
     */
    private array $middlewareDefinitions;

    /**
     * @var MessageHandlerPushInterface The final push handler in the middleware chain, responsible
     * for actually sending the message. Uses {@see SynchronousPushHandler} in synchronous mode or
     * {@see AdapterPushHandler} otherwise.
     */
    private MessageHandlerPushInterface $finalPushHandler;

    private string $name;

    /**
     * @var PushMiddlewareDispatcher The dispatcher used for push messages, combining base dispatcher middleware with
     * queue-specific middleware.
     */
    private PushMiddlewareDispatcher $dispatcher;

    /**
     * @param WorkerInterface $worker The worker that processes messages.
     * @param LoopInterface $loop The loop for controlling message processing.
     * @param LoggerInterface $logger The logger for debug and informational messages.
     * @param PushMiddlewareDispatcher $baseDispatcher The middleware dispatcher.
     * @param AdapterInterface|null $adapter The message adapter (`null` for synchronous mode).
     * @param string|BackedEnum $name The queue name.
     * @param MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions Queue-specific middleware
     * definitions.
     */
    public function __construct(
        private readonly WorkerInterface $worker,
        private readonly LoopInterface $loop,
        private readonly LoggerInterface $logger,
        private readonly PushMiddlewareDispatcher $baseDispatcher,
        private readonly ?AdapterInterface $adapter = null,
        string|BackedEnum $name = QueueProviderInterface::DEFAULT_QUEUE,
        MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions,
    ) {
        $this->name = StringNormalizer::normalize($name);
        $this->finalPushHandler = $this->isSynchronous()
            ? new SynchronousPushHandler($worker, $this)
            : new AdapterPushHandler($this->adapter);
        $this->setMiddlewaresAndPrepareDispatcher($middlewareDefinitions);
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

        $message = $this->dispatcher->dispatch($message, $this->finalPushHandler);

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
        $instance->setMiddlewaresAndPrepareDispatcher($middlewareDefinitions);
        return $instance;
    }

    public function withMiddlewaresAdded(MiddlewarePushInterface|callable|array|string ...$middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->setMiddlewaresAndPrepareDispatcher([...array_values($instance->middlewareDefinitions), ...array_values($middlewareDefinitions)]);
        return $instance;
    }

    /**
     * @param array<MiddlewarePushInterface|callable|array|string> $middlewareDefinitions
     */
    private function setMiddlewaresAndPrepareDispatcher(array $middlewareDefinitions): void
    {
        $this->middlewareDefinitions = $middlewareDefinitions;
        $this->dispatcher = $this->baseDispatcher->withMiddlewaresAdded($middlewareDefinitions);
    }

    private function handle(MessageInterface $message): bool
    {
        $this->worker->process($message, $this);

        return $this->loop->canContinue();
    }

    /**
     * @psalm-assert-if-false !null $this->adapter
     */
    private function isSynchronous(): bool
    {
        return $this->adapter === null;
    }
}
