<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message\Behaviors;

/**
 * Interface for queue driver behaviors which can change their state on message pushing
 */
interface ExecutableBehaviorInterface extends BehaviorInterface
{
    /**
     * Changes behavior state if necessary
     */
    public function execute(): void;
}
