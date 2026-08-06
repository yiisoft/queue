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

    public function getProducer(string|BackedEnum $name): QueueProducerInterface
    {
        foreach ($this->producerProviders as $provider) {
            if ($provider->hasProducer($name)) {
                return $provider->getProducer($name);
            }
        }
        throw new QueueNotFoundException(StringNormalizer::normalize($name));
    }

    public function hasProducer(string|BackedEnum $name): bool
    {
        foreach ($this->producerProviders as $p) {
            if ($p->hasProducer($name)) {
                return true;
            }
        } return false;
    }

    /** @return list<string> */
    public function getProducerNames(): array
    {
        $result = [];
        foreach ($this->producerProviders as $provider) {
            foreach ($provider->getProducerNames() as $name) {
                if (!in_array($name, $result, true)) {
                    $result[] = $name;
                }
            }
        } return $result;
    }

    public function getConsumer(string|BackedEnum $name): QueueConsumerInterface
    {
        foreach ($this->consumerProviders as $provider) {
            if ($provider->hasConsumer($name)) {
                return $provider->getConsumer($name);
            }
        }
        throw new QueueNotFoundException(StringNormalizer::normalize($name));
    }

    public function hasConsumer(string|BackedEnum $name): bool
    {
        foreach ($this->consumerProviders as $p) {
            if ($p->hasConsumer($name)) {
                return true;
            }
        } return false;
    }

    /** @return list<string> */
    public function getConsumerNames(): array
    {
        $result = [];
        foreach ($this->consumerProviders as $provider) {
            foreach ($provider->getConsumerNames() as $name) {
                if (!in_array($name, $result, true)) {
                    $result[] = $name;
                }
            }
        } return $result;
    }
}
