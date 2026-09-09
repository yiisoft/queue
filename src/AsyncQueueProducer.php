<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use BackedEnum;
use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\AdapterPushHandler;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Push\PushRequest;

/**
 * Produces messages for one logical queue, pushing them to an adapter-backed broker.
 */
final class AsyncQueueProducer
{
    private string $queueName;
    private PushMiddlewareDispatcher $dispatcher;
    private QueueProducerStatusInterface $status;

    /**
     * @param mixed[] $middlewareDefinitions Queue-specific push middleware definitions.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        PushMiddlewareConfig $middlewareConfig,
        private readonly AdapterInterface $adapter,
        string|BackedEnum $queueName = DefaultQueue::NAME,
        array $middlewareDefinitions = [],
        ?QueueProducerStatusInterface $status = null,
    ) {
        $this->status = $status ?? new AdapterQueueProducerStatus($adapter);
        $this->queueName = StringNormalizer::normalize($queueName);
        $this->dispatcher = new PushMiddlewareDispatcher(
            middlewareFactory: $middlewareConfig->middlewareFactory,
            middlewareDefinitions: [...$middlewareConfig->commonMiddlewareDefinitions, ...$middlewareDefinitions],
            finishHandler: new AdapterPushHandler($adapter),
        );
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $this->logger->debug(
            'Preparing to push message with message type "{messageType}".',
            ['messageType' => $message->getType()],
        );
        $message = $this->dispatcher->hasMiddlewares()
            ? $this->dispatcher->dispatch(new PushRequest($message, $this->queueName))->getMessage()
            : $this->adapter->push($message);
        $id = IdEnvelope::fromMessage($message)->getId();
        $this->logger->info(
            $id === null
                ? 'Pushed message with message type "{messageType}" to the queue. ID doesn\'t assigned.'
                : 'Pushed message with message type "{messageType}" to the queue. Assigned ID #{id}.',
            ['messageType' => $message->getType(), 'id' => $id],
        );
        return $message;
    }

    public function getStatus(): QueueProducerStatusInterface
    {
        return $this->status;
    }
}
