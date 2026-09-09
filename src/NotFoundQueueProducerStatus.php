<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

final class NotFoundQueueProducerStatus implements QueueProducerStatusInterface
{
    public function status(string|int $id): MessageStatus
    {
        return MessageStatus::NOT_FOUND;
    }
}
