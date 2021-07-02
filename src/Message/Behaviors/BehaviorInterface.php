<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

/**
 * Interface for behaviors to be used by queue adapters.
 */
interface BehaviorInterface
{
    /**
     * Factory method for the current behavior
     *
     * @param mixed $data Dataset returned by {@see getSerializableData}
     *
     * @return BehaviorInterface
     */
    public static function fromData($data): self;

    /**
     * Returns current state data as a json-serializable dataset.
     * Behavior will be restored with this data through {@see fromData}
     *
     * @return mixed
     */
    public function getSerializableData();
}
