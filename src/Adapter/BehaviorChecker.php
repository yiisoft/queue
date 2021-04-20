<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Adapter;

use Yiisoft\Yii\Queue\Exception\BehaviorNotSupportedException;

final class BehaviorChecker
{
    public function check(string $adapter, iterable $behaviorsCurrent, iterable $behaviorsAvailable): void
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
                throw new BehaviorNotSupportedException($adapter, $behavior);
            }
        }
    }
}
