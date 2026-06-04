<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use BackedEnum;
use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\AdapterPushHandler;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Push\SynchronousPushHandler;
use Yiisoft\Queue\Worker\WorkerInterface;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Provider\QueueProviderInterface;

final class Queue implements QueueInterface
{
    /**
     * @var mixed[] Queue-specific middleware definitions.
     */
    private array $middlewareDefinitions;

    private string $name;

    /**
     * @var PushMiddlewareDispatcher The dispatcher used for push messages, combining base dispatcher middleware with
     * queue-specific middleware.
     */
    private PushMiddlewareDispatcher $dispatcher;

    /**
     * @var PushMiddlewareDispatcher The base dispatcher built from {@see PushMiddlewareConfig}.
     * Holds the common middleware applied to all queues.
     */
    private PushMiddlewareDispatcher $baseDispatcher;

    /**
     * @param WorkerInterface $worker The worker that processes messages.
     * @param LoopInterface $loop The loop for controlling message processing.
     * @param LoggerInterface $logger The logger for debug and informational messages.
     * @param PushMiddlewareConfig $middlewareConfig The push middleware configuration: factory and common middleware
     * definitions.
     * @param AdapterInterface|null $adapter The message adapter (`null` for synchronous mode).
     * @param string|BackedEnum $name The queue name.
     * @param mixed ...$middlewareDefinitions Queue-specific middleware definitions.
     */
    public function __construct(
        private readonly WorkerInterface $worker,
        private readonly LoopInterface $loop,
        private readonly LoggerInterface $logger,
        PushMiddlewareConfig $middlewareConfig,
        private readonly ?AdapterInterface $adapter = null,
        string|BackedEnum $name = QueueProviderInterface::DEFAULT_QUEUE,
        mixed ...$middlewareDefinitions,
    ) {
        $this->name = StringNormalizer::normalize($name);
        $this->baseDispatcher = new PushMiddlewareDispatcher(
            $middlewareConfig->middlewareFactory,
            $middlewareConfig->commonMiddlewareDefinitions,
            $this->createFinalPushHandler(),
        );
        $this->setMiddlewaresAndPrepareDispatcher($middlewareDefinitions);
    }

    public function __clone()
    {
        $finalPushHandler = $this->createFinalPushHandler();
        $this->baseDispatcher = $this->baseDispatcher->withFinishHandler($finalPushHandler);
        $this->dispatcher = $this->dispatcher->withFinishHandler($finalPushHandler);
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

        $message = $this->dispatcher->dispatch($message);

        if ($this->isSynchronous()) {
            $this->logger->info(
                'Processed message with message type "{messageType}" synchronously.',
                ['messageType' => $message->getType()],
            );
            return $message;
        }

        $messageId = $message->getMetadata()[IdEnvelope::META_ID] ?? 'null';
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

    public function withMiddlewares(mixed ...$middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->setMiddlewaresAndPrepareDispatcher($middlewareDefinitions);
        return $instance;
    }

    public function withMiddlewaresAdded(mixed ...$middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->setMiddlewaresAndPrepareDispatcher([...array_values($instance->middlewareDefinitions), ...array_values($middlewareDefinitions)]);
        return $instance;
    }

    /**
     * @param mixed[] $middlewareDefinitions
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

    private function createFinalPushHandler(): PushHandlerInterface
    {
        return $this->isSynchronous()
            ? new SynchronousPushHandler($this->worker, $this)
            : new AdapterPushHandler($this->adapter);
    }

    /**
     * @psalm-assert-if-false !null $this->adapter
     */
    private function isSynchronous(): bool
    {
        return $this->adapter === null;
    }
}
