<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueInterface;

/**
 * `QueueProviderInterface` provides a way to get a queue instance by name.
 */
interface QueueProviderInterface
{
    /** @psalm-suppress MissingClassConstType */
    public const DEFAULT_QUEUE = 'yii-queue';

    /**
     * Finds a queue by name and returns it.
     *
     * @param BackedEnum|string $queueName Queue name.
     *
     * @throws InvalidQueueConfigException If the queue configuration is invalid.
     * @throws QueueNotFoundException If the queue is not found.
     * @throws QueueProviderException If the queue provider fails to provide a queue.
     * @return QueueInterface Queue instance.
     */
    public function get(string|BackedEnum $queueName): QueueInterface;

    /**
     * Check if a queue with the specified name exists.
     *
     * @param BackedEnum|string $queueName Queue name.
     *
     * @return bool Whether the queue exists.
     */
    public function has(string|BackedEnum $queueName): bool;
}
