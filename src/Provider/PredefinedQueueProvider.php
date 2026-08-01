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
    private array $producerNames = [];
    /** @var list<string> */
    private array $consumerNames = [];

    /** @param array<string, mixed> $queues */
    public function __construct(array $queues)
    {
        foreach ($queues as $name => $roles) {
            if (!is_array($roles) || $roles === []) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" must be a non-empty role map containing ready "producer" and/or "consumer" instances.', $name));
            }
            $unknown = array_diff(array_keys($roles), ['producer', 'consumer']);
            if ($unknown !== []) {
                throw new InvalidQueueConfigException(sprintf('Queue "%s" has unknown role key(s) "%s". Only "producer" and "consumer" are allowed.', $name, implode('", "', $unknown)));
            }
            foreach ($roles as $role => $queue) {
                $expected = $role === 'producer' ? QueueProducerInterface::class : QueueConsumerInterface::class;
                if (!$queue instanceof $expected) {
                    $hint = is_array($queue) || is_string($queue) ? ' Use QueueFactoryProvider for factory definitions.' : '';
                    throw new InvalidQueueConfigException(sprintf(
                        'Queue "%s" role "%s" must be a ready instance of "%s"; got "%s" (configuration path queues.%s.%s).%s',
                        $name,
                        $role,
                        $expected,
                        get_debug_type($queue),
                        $name,
                        $role,
                        $hint,
                    ));
                }
            }
            /** @var array<string, QueueProducerInterface|QueueConsumerInterface> $roles */
            $this->queues[$name] = $roles;
            if (array_key_exists('producer', $roles)) {
                $this->producerNames[] = $name;
            }
            if (array_key_exists('consumer', $roles)) {
                $this->consumerNames[] = $name;
            }
        }
    }

    public function getProducer(string|BackedEnum $name): QueueProducerInterface
    {
        $queue = $this->get($name, 'producer');
        assert($queue instanceof QueueProducerInterface);
        return $queue;
    }

    public function hasProducer(string|BackedEnum $name): bool
    {
        return array_key_exists('producer', $this->queues[StringNormalizer::normalize($name)] ?? []);
    }

    public function getProducerNames(): array
    {
        return $this->producerNames;
    }

    public function getConsumer(string|BackedEnum $name): QueueConsumerInterface
    {
        $queue = $this->get($name, 'consumer');
        assert($queue instanceof QueueConsumerInterface);
        return $queue;
    }

    public function hasConsumer(string|BackedEnum $name): bool
    {
        return array_key_exists('consumer', $this->queues[StringNormalizer::normalize($name)] ?? []);
    }

    public function getConsumerNames(): array
    {
        return $this->consumerNames;
    }

    private function get(string|BackedEnum $name, string $role): QueueProducerInterface|QueueConsumerInterface
    {
        $name = StringNormalizer::normalize($name);
        if (!array_key_exists($name, $this->queues)) {
            throw new QueueNotFoundException($name);
        }
        if (!array_key_exists($role, $this->queues[$name])) {
            throw new QueueNotFoundException(sprintf('Queue "%s" does not have the "%s" capability.', $name, $role));
        }
        return $this->queues[$name][$role];
    }
}
