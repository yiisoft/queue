<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Job;

interface PrioritisedJobInterface extends JobInterface
{
    public function getPriority(): int;
}
