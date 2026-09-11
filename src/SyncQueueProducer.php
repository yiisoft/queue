<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use BackedEnum;
use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Push\SynchronousPushHandler;
use Yiisoft\Queue\Worker\WorkerInterface;

/**
 * Producer that runs each message synchronously, in the same process as the caller.
 */
final class SyncQueueProducer implements QueueProducerInterface
{
    private string $queueName;
    private PushMiddlewareDispatcher $dispatcher;

    /**
     * @param mixed[] $middlewareDefinitions Queue-specific push middleware definitions.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        PushMiddlewareConfig $middlewareConfig,
        WorkerInterface $worker,
        string|BackedEnum $queueName = DefaultQueue::NAME,
        array $middlewareDefinitions = [],
    ) {
        $this->queueName = StringNormalizer::normalize($queueName);
        $this->dispatcher = new PushMiddlewareDispatcher(
            middlewareFactory: $middlewareConfig->middlewareFactory,
            middlewareDefinitions: [...$middlewareConfig->commonMiddlewareDefinitions, ...$middlewareDefinitions],
            finalHandler: new SynchronousPushHandler($worker, $this->queueName),
        );
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $this->logger->debug('Preparing to push message with message type "{messageType}".', ['messageType' => $message->getType()]);
        $message = $this->dispatcher->dispatch($message);
        $this->logger->info('Processed message with message type "{messageType}" synchronously.', ['messageType' => $message->getType()]);
        return $message;
    }

    public function status(string|int $id): MessageStatus
    {
        return MessageStatus::NOT_FOUND;
    }
}
