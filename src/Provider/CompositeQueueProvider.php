<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\QueueProducerInterface;
use Yiisoft\Queue\StringNormalizer;

use function in_array;

/** Combines typed providers; earlier providers take precedence per capability. */
final class CompositeQueueProvider implements QueueProducerProviderInterface, QueueConsumerProviderInterface
{
    /** @var list<QueueProducerProviderInterface> */ private array $producerProviders = [];
    /** @var list<QueueConsumerProviderInterface> */ private array $consumerProviders = [];

    public function __construct(QueueProducerProviderInterface|QueueConsumerProviderInterface ...$providers)
    {
        foreach ($providers as $provider) {
            if ($provider instanceof QueueProducerProviderInterface) {
                $this->producerProviders[] = $provider;
            }
            if ($provider instanceof QueueConsumerProviderInterface) {
                $this->consumerProviders[] = $provider;
            }
        }
    }

    public function getProducer(string|BackedEnum $queueName): QueueProducerInterface
    {
        foreach ($this->producerProviders as $provider) {
            if ($provider->hasProducer($queueName)) {
                return $provider->getProducer($queueName);
            }
        }
        throw new QueueNotFoundException(StringNormalizer::normalize($queueName));
    }

    public function hasProducer(string|BackedEnum $queueName): bool
    {
        foreach ($this->producerProviders as $p) {
            if ($p->hasProducer($queueName)) {
                return true;
            }
        } return false;
    }

    /** @return list<string> */
    public function getProducerQueueNames(): array
    {
        $result = [];
        foreach ($this->producerProviders as $provider) {
            foreach ($provider->getProducerQueueNames() as $queueName) {
                if (!in_array($queueName, $result, true)) {
                    $result[] = $queueName;
                }
            }
        } return $result;
    }

    public function getConsumer(string|BackedEnum $queueName): QueueConsumerInterface
    {
        foreach ($this->consumerProviders as $provider) {
            if ($provider->hasConsumer($queueName)) {
                return $provider->getConsumer($queueName);
            }
        }
        throw new QueueNotFoundException(StringNormalizer::normalize($queueName));
    }

    public function hasConsumer(string|BackedEnum $queueName): bool
    {
        foreach ($this->consumerProviders as $p) {
            if ($p->hasConsumer($queueName)) {
                return true;
            }
        } return false;
    }

    /** @return list<string> */
    public function getConsumerQueueNames(): array
    {
        $result = [];
        foreach ($this->consumerProviders as $provider) {
            foreach ($provider->getConsumerQueueNames() as $queueName) {
                if (!in_array($queueName, $result, true)) {
                    $result[] = $queueName;
                }
            }
        } return $result;
    }
}
