<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Payload;

/**
 * Retryable Payload Interface.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
interface AttemptsRestrictedPayloadInterface extends PayloadInterface
{
    public function getAttempts(): int;
}
