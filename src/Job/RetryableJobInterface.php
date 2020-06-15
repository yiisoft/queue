<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Job;

use Throwable;

/**
 * Retryable Job Interface.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
interface RetryableJobInterface extends JobInterface
{
    /**
     * @return int time to reserve in seconds
     */
    public function getTtr(): int;

    /**
     * @param Throwable|null $error
     *
     * @return bool
     */
    public function canRetry(?Throwable $error = null): bool;

    public function retry(): void;
}
