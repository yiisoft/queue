<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueProducerStatusInterface;

/** Provides the status capability exposed by configured concrete producers. */
final class QueueProducerStatusProvider implements QueueProducerStatusProviderInterface
{
    public function __construct(private readonly QueueProducerProviderInterface $producerProvider) {}

    public function getStatus(string|BackedEnum $queueName): QueueProducerStatusInterface
    {
        return $this->producerProvider->getProducer($queueName)->getStatus();
    }

    public function hasStatus(string|BackedEnum $queueName): bool
    {
        return $this->producerProvider->hasProducer($queueName);
    }

    /** @return list<string> */
    public function getStatusQueueNames(): array
    {
        return $this->producerProvider->getProducerQueueNames();
    }
}
