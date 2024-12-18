<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use BackedEnum;
use Yiisoft\Queue\QueueInterface;

/**
 * `QueueProviderInterface` provides a way to get a queue instance by channel name.
 */
interface QueueProviderInterface
{
    /**
     * Find a queue by channel name and returns it.
     *
     * @param BackedEnum|string $channel Channel name.
     *
     * @throws InvalidQueueConfigException If the queue configuration is invalid.
     * @throws ChannelNotFoundException If the channel is not found.
     * @throws QueueProviderException If the queue provider fails to provide a queue.
     * @return QueueInterface Queue instance.
     */
    public function get(string|BackedEnum $channel): QueueInterface;

    /**
     * Check if a queue with the specified channel name exists.
     *
     * @param BackedEnum|string $channel Channel name.
     *
     * @return bool Whether the queue exists.
     */
    public function has(string|BackedEnum $channel): bool;
}
