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
    private array $producerQueues = [];
    /** @var list<string> */
    private array $consumerQueues = [];

    /** @param array<string, mixed> $queues */
    public function __construct(array $queues)
    {
        foreach ($queues as $queue => $roles) {
            if (!is_array($roles) || $roles === []) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" must be a non-empty role map containing ready "producer" and/or "consumer" instances.', $queue));
            }
            $unknown = array_diff(array_keys($roles), ['producer', 'consumer']);
            if ($unknown !== []) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" has unknown role key(s) "%s". Only "producer" and "consumer" are allowed.', $queue, implode('", "', $unknown)));
            }
            foreach ($roles as $role => $instance) {
                $expected = $role === 'producer' ? QueueProducerInterface::class : QueueConsumerInterface::class;
                if (!$instance instanceof $expected) {
                    $hint = is_array($instance) || is_string($instance) ? ' Use QueueFactoryProvider for factory definitions.' : '';
                    throw new InvalidQueueConfigException(sprintf(
                        'Queue "%s" role "%s" must be a ready instance of "%s"; got "%s" (configuration path queues.%s.%s).%s',
                        $queue,
                        $role,
                        $expected,
                        get_debug_type($instance),
                        $queue,
                        $role,
                        $hint,
                    ));
                }
            }
            /** @var array<string, QueueProducerInterface|QueueConsumerInterface> $roles */
            $this->queues[$queue] = $roles;
            if (array_key_exists('producer', $roles)) {
                $this->producerQueues[] = $queue;
            }
            if (array_key_exists('consumer', $roles)) {
                $this->consumerQueues[] = $queue;
            }
        }
    }

    public function getProducer(string|BackedEnum $queue): QueueProducerInterface
    {
        $instance = $this->get($queue, 'producer');
        assert($instance instanceof QueueProducerInterface);
        return $instance;
    }

    public function hasProducer(string|BackedEnum $queue): bool
    {
        return array_key_exists('producer', $this->queues[StringNormalizer::normalize($queue)] ?? []);
    }

    public function getProducerQueues(): array
    {
        return $this->producerQueues;
    }

    public function getConsumer(string|BackedEnum $queue): QueueConsumerInterface
    {
        $instance = $this->get($queue, 'consumer');
        assert($instance instanceof QueueConsumerInterface);
        return $instance;
    }

    public function hasConsumer(string|BackedEnum $queue): bool
    {
        return array_key_exists('consumer', $this->queues[StringNormalizer::normalize($queue)] ?? []);
    }

    public function getConsumerQueues(): array
    {
        return $this->consumerQueues;
    }

    private function get(string|BackedEnum $queue, string $role): QueueProducerInterface|QueueConsumerInterface
    {
        $queue = StringNormalizer::normalize($queue);
        if (!array_key_exists($queue, $this->queues)) {
            throw new QueueNotFoundException($queue);
        }
        if (!array_key_exists($role, $this->queues[$queue])) {
            throw new QueueNotFoundException(sprintf('Queue "%s" does not have the "%s" capability.', $queue, $role));
        }
        return $this->queues[$queue][$role];
    }
}
