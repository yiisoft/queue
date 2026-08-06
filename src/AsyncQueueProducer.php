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
use Yiisoft\Queue\Provider\QueueProducerProviderInterface;

/**
 * Produces messages for one logical queue, pushing them to an adapter-backed broker.
 */
final class AsyncQueueProducer implements QueueProducerInterface
{
    private string $name;
    private PushMiddlewareDispatcher $dispatcher;

    /**
     * @param mixed[] $middlewareDefinitions Queue-specific push middleware definitions.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        PushMiddlewareConfig $middlewareConfig,
        private readonly AdapterInterface $adapter,
        string|BackedEnum $name = QueueProducerProviderInterface::DEFAULT_QUEUE,
        array $middlewareDefinitions = [],
    ) {
        $this->name = StringNormalizer::normalize($name);
        $this->dispatcher = new PushMiddlewareDispatcher(
            middlewareFactory: $middlewareConfig->middlewareFactory,
            middlewareDefinitions: [...$middlewareConfig->commonMiddlewareDefinitions, ...$middlewareDefinitions],
            finishHandler: new AdapterPushHandler($adapter),
        );
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
        $id = IdEnvelope::fromMessage($message)->getId();
        $this->logger->info(
            $id === null
                ? 'Pushed message with message type "{messageType}" to the queue. ID doesn\'t assigned.'
                : 'Pushed message with message type "{messageType}" to the queue. Assigned ID #{id}.',
            ['messageType' => $message->getType(), 'id' => $id],
        );
        return $message;
    }

    public function status(string|int $id): MessageStatus
    {
        return $this->adapter->status($id);
    }
}
