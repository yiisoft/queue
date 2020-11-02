<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver;

use Yiisoft\Yii\Queue\Exception\BehaviorNotSupportedException;

class BehaviorChecker implements BehaviorCheckerInterface
{
    public function check(string $driver, iterable $behaviorsCurrent, iterable $behaviorsAvailable)
    {
        foreach ($behaviorsCurrent as $behavior) {
            $ok = false;
            foreach ($behaviorsAvailable as $available) {
                if ($behavior instanceof $available) {
                    $ok = true;
                    break;
                }
            }

            if ($ok === false) {
                throw new BehaviorNotSupportedException($driver, $behavior);
            }
        }
    }
}
