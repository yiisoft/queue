<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

/**
 * Interface for behaviors to be used by queue drivers
 */
interface BehaviorInterface
{
    /**
     * Returns current state data as an array of constructor parameters.
     * Behavior will be restored with this data after serializing and deserializing
     *
     * @return string[]
     */
    public function getConstructorParameters(): array;
}
