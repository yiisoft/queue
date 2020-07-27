<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

interface QueueDependentInterface
{
    public function setQueue(Queue $queue): void;
}
