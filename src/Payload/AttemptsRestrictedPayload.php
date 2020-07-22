<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Payload;

use Throwable;

abstract class AttemptsRestrictedPayload implements RetryablePayloadInterface
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
