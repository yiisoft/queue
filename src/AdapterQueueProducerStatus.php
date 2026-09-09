<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use Yiisoft\Queue\Adapter\AdapterInterface;

final class AdapterQueueProducerStatus implements QueueProducerStatusInterface
{
    public function __construct(private readonly AdapterInterface $adapter) {}

    public function status(string|int $id): MessageStatus
    {
        return $this->adapter->status($id);
    }
}
