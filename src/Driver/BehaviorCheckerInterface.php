<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver;

use Yiisoft\Yii\Queue\Exception\BehaviorNotSupportedException;

interface BehaviorCheckerInterface
{
    /**
     * Checks if all the given behaviors are available in the available set.
     *
     * @param string $driver Driver name.
     * @param iterable $behaviorsCurrent
     * @param iterable $behaviorsAvailable
     *
     * @throws BehaviorNotSupportedException Must be thrown if the check doesn't pass.
     */
    public function check(string $driver, iterable $behaviorsCurrent, iterable $behaviorsAvailable);
}
