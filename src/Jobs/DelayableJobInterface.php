<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Jobs;

interface DelayableJobInterface extends JobInterface
{
    public function getDelay(): int;
}
