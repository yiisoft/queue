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

    public function getProducer(string|BackedEnum $queue): QueueProducerInterface
    {
        foreach ($this->producerProviders as $provider) {
            if ($provider->hasProducer($queue)) {
                return $provider->getProducer($queue);
            }
        }
        throw new QueueNotFoundException(StringNormalizer::normalize($queue));
    }

    public function hasProducer(string|BackedEnum $queue): bool
    {
        foreach ($this->producerProviders as $p) {
            if ($p->hasProducer($queue)) {
                return true;
            }
        } return false;
    }

    /** @return list<string> */
    public function getProducerQueues(): array
    {
        $result = [];
        foreach ($this->producerProviders as $provider) {
            foreach ($provider->getProducerQueues() as $queue) {
                if (!in_array($queue, $result, true)) {
                    $result[] = $queue;
                }
            }
        } return $result;
    }

    public function getConsumer(string|BackedEnum $queue): QueueConsumerInterface
    {
        foreach ($this->consumerProviders as $provider) {
            if ($provider->hasConsumer($queue)) {
                return $provider->getConsumer($queue);
            }
        }
        throw new QueueNotFoundException(StringNormalizer::normalize($queue));
    }

    public function hasConsumer(string|BackedEnum $queue): bool
    {
        foreach ($this->consumerProviders as $p) {
            if ($p->hasConsumer($queue)) {
                return true;
            }
        } return false;
    }

    /** @return list<string> */
    public function getConsumerQueues(): array
    {
        $result = [];
        foreach ($this->consumerProviders as $provider) {
            foreach ($provider->getConsumerQueues() as $queue) {
                if (!in_array($queue, $result, true)) {
                    $result[] = $queue;
                }
            }
        } return $result;
    }
}
