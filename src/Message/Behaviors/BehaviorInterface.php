<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

/**
 * Interface for behaviors to be used by queue drivers
 */
interface BehaviorInterface
{
    /**
     * Returns current behavior state data as an array of strings (e.g. ["stateName' => "stateValue"])
     *
     * @return string[]
     */
    public function getState(): array;
}
