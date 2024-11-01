<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use Yiisoft\Queue\QueueInterface;

interface QueueProviderInterface
{
    /**
     * @throws InvalidQueueConfigException
     * @throws ChannelNotFoundException
     * @throws QueueProviderException
     */
    public function get(string $channel): QueueInterface;

    public function has(string $channel): bool;
}
