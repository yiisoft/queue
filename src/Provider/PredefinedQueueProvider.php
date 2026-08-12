<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\QueueProducerInterface;
use Yiisoft\Queue\StringNormalizer;

use function array_key_exists;
use function array_keys;
use function get_debug_type;
use function implode;
use function is_array;
use function sprintf;
use function assert;
use function is_string;

/** Provides already-created producer and consumer instances from strict role maps. */
final class PredefinedQueueProvider implements QueueProducerProviderInterface, QueueConsumerProviderInterface
{
    /** @var array<string, array<string, QueueProducerInterface|QueueConsumerInterface>> */
    private array $queues = [];
    /** @var list<string> */
    private array $producerQueueNames = [];
    /** @var list<string> */
    private array $consumerQueueNames = [];

    /** @param array<string, mixed> $queues */
    public function __construct(array $queues)
    {
        foreach ($queues as $queueName => $roles) {
            if (!is_array($roles) || $roles === []) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" must be a non-empty role map containing ready "producer" and/or "consumer" instances.', $queueName));
            }
            $unknown = array_diff(array_keys($roles), ['producer', 'consumer']);
            if ($unknown !== []) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" has unknown role key(s) "%s". Only "producer" and "consumer" are allowed.', $queueName, implode('", "', $unknown)));
            }
            foreach ($roles as $role => $instance) {
                $expected = $role === 'producer' ? QueueProducerInterface::class : QueueConsumerInterface::class;
                if (!$instance instanceof $expected) {
                    $hint = is_array($instance) || is_string($instance) ? ' Use QueueFactoryProvider for factory definitions.' : '';
                    throw new InvalidQueueConfigException(sprintf(
                        'Queue "%s" role "%s" must be a ready instance of "%s"; got "%s" (configuration path queues.%s.%s).%s',
                        $queueName,
                        $role,
                        $expected,
                        get_debug_type($instance),
                        $queueName,
                        $role,
                        $hint,
                    ));
                }
            }
            /** @var array<string, QueueProducerInterface|QueueConsumerInterface> $roles */
            $this->queues[$queueName] = $roles;
            if (array_key_exists('producer', $roles)) {
                $this->producerQueueNames[] = $queueName;
            }
            if (array_key_exists('consumer', $roles)) {
                $this->consumerQueueNames[] = $queueName;
            }
        }
    }

    public function getProducer(string|BackedEnum $queueName): QueueProducerInterface
    {
        $instance = $this->get($queueName, 'producer');
        assert($instance instanceof QueueProducerInterface);
        return $instance;
    }

    public function hasProducer(string|BackedEnum $queueName): bool
    {
        return array_key_exists('producer', $this->queues[StringNormalizer::normalize($queueName)] ?? []);
    }

    public function getProducerQueueNames(): array
    {
        return $this->producerQueueNames;
    }

    public function getConsumer(string|BackedEnum $queueName): QueueConsumerInterface
    {
        $instance = $this->get($queueName, 'consumer');
        assert($instance instanceof QueueConsumerInterface);
        return $instance;
    }

    public function hasConsumer(string|BackedEnum $queueName): bool
    {
        return array_key_exists('consumer', $this->queues[StringNormalizer::normalize($queueName)] ?? []);
    }

    public function getConsumerQueueNames(): array
    {
        return $this->consumerQueueNames;
    }

    private function get(string|BackedEnum $queueName, string $role): QueueProducerInterface|QueueConsumerInterface
    {
        $queueName = StringNormalizer::normalize($queueName);
        if (!array_key_exists($queueName, $this->queues)) {
            throw new QueueNotFoundException($queueName);
        }
        if (!array_key_exists($role, $this->queues[$queueName])) {
            throw new QueueNotFoundException(sprintf('Queue "%s" does not have the "%s" capability.', $queueName, $role));
        }
        return $this->queues[$queueName][$role];
    }
}
