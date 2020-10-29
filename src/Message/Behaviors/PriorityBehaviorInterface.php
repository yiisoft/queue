<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

interface PriorityBehaviorInterface
{
    /**
     * The higher the priority, the earlier will be consumed the message
     *
     * @return int
     */
    public function getPriority(): int;
}
