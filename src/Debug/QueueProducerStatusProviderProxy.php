<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use BackedEnum;
use Yiisoft\Queue\Provider\QueueProducerStatusProviderInterface;
use Yiisoft\Queue\QueueProducerStatusInterface;
use Yiisoft\Queue\StringNormalizer;

final class QueueProducerStatusProviderProxy implements QueueProducerStatusProviderInterface
{
    public function __construct(
        private readonly QueueProducerStatusProviderInterface $provider,
        private readonly QueueCollector $collector,
    ) {}

    public function getStatus(string|BackedEnum $queueName): QueueProducerStatusInterface
    {
        return new QueueProducerStatusProxy(
            $this->provider->getStatus(StringNormalizer::normalize($queueName)),
            $this->collector,
        );
    }

    public function hasStatus(string|BackedEnum $queueName): bool
    {
        return $this->provider->hasStatus(StringNormalizer::normalize($queueName));
    }

    public function getStatusQueueNames(): array
    {
        return $this->provider->getStatusQueueNames();
    }
}
