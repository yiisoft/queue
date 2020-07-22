<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Payload;

use Throwable;

/**
 * Retryable Payload Interface.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
interface RetryablePayloadInterface extends PayloadInterface
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
