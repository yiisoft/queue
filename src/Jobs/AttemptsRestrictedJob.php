<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Jobs;

use Throwable;

abstract class AttemptsRestrictedJob implements RetryableJobInterface
{
    protected int $attemptsMax = 1;
    protected int $attempt = 1;

    public function canRetry(?Throwable $error = null): bool
    {
        return $this->attemptsMax > $this->attempt;
    }

    public function retry(): void
    {
        $this->attempt++;
    }
}
