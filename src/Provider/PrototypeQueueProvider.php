<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Provider;

use Yiisoft\Queue\QueueInterface;

final class PrototypeQueueProvider implements QueueProviderInterface
{
    public function __construct(
        private readonly QueueInterface $baseQueue,
    ) {
    }

    public function get(string $channel): QueueInterface
    {
        return $this->baseQueue->withChannelName($channel);
    }

    public function has(string $channel): bool
    {
        return true;
    }
}
