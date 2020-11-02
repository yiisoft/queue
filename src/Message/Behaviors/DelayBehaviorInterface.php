<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

interface DelayBehaviorInterface
{
    /**
     * Delay in seconds before the message can be consumed.
     *
     * @return int
     */
    public function getDelay(): int;
}
