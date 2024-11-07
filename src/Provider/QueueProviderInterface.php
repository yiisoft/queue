<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use Yiisoft\Queue\QueueInterface;

/**
 * `QueueProviderInterface` provides a way to get a queue instance by channel name.
 */
interface QueueProviderInterface
{
    /**
     * Find a queue by channel name and returns it.
     *
     * @param string $channel Channel name.
     *
     * @return QueueInterface Queue instance.
     *
     * @throws InvalidQueueConfigException If the queue configuration is invalid.
     * @throws ChannelNotFoundException If the channel is not found.
     * @throws QueueProviderException If the queue provider fails to provide a queue.
     */
    public function get(string $channel): QueueInterface;

    /**
     * Check if a queue with the specified channel name exists.
     *
     * @param string $channel Channel name.
     *
     * @return bool Whether the queue exists.
     */
    public function has(string $channel): bool;
}
