<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

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
