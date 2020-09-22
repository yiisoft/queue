<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Payload;

/**
 * Retryable Payload Interface.
 */
interface AttemptsRestrictedPayloadInterface extends PayloadInterface
{
    public function getAttempts(): int;
}
