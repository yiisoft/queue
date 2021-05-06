<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

interface QueueFactoryInterface
{
    public function get(?string $channel = null): QueueInterface;
}
