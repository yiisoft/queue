<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\StringNormalizer;

use function array_key_exists;
use function array_keys;
use function get_debug_type;
use function sprintf;

/**
 * Queue provider that uses a pre-defined map of queue name to queue instance.
 */
final class PredefinedQueueProvider implements QueueProviderInterface
{
    /**
     * @psalm-var array<string, QueueInterface>
     */
    private readonly array $queues;

    /**
     * @param array $queues Map of queue name to queue instance.
     *
     * @psalm-param array<string, QueueInterface> $queues
     *
     * @throws InvalidQueueConfigException If a value in the array is not a {@see QueueInterface} instance.
     */
    public function __construct(array $queues)
    {
        foreach ($queues as $name => $queue) {
            if (!$queue instanceof QueueInterface) {
                throw new InvalidQueueConfigException(
                    sprintf(
                        'Queue must implement "%s". For queue "%s" got "%s" instead.',
                        QueueInterface::class,
                        $name,
                        get_debug_type($queue),
                    ),
                );
            }
        }
        $this->queues = $queues;
    }

    public function get(string|BackedEnum $name): QueueInterface
    {
        $name = StringNormalizer::normalize($name);

        if (!array_key_exists($name, $this->queues)) {
            throw new QueueNotFoundException($name);
        }

        return $this->queues[$name];
    }

    public function has(string|BackedEnum $name): bool
    {
        $name = StringNormalizer::normalize($name);
        return array_key_exists($name, $this->queues);
    }

    public function getNames(): array
    {
        return array_keys($this->queues);
    }
}
