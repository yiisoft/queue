<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

interface QueueProducerStatusInterface
{
    public function status(string|int $id): MessageStatus;
}
