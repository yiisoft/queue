<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue;

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
     * @param int $attempt number
     * @param \Exception|\Throwable $error from last execute of the job
     *
     * @return bool
     */
    public function canRetry(int $attempt, $error): bool;
}
