<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Yii\Queue\Payload\AttemptsRestrictedPayloadInterface;

class RetryablePayload extends SimplePayload implements AttemptsRestrictedPayloadInterface
{
    protected string $name = 'retryable';

    public function getAttempts(): int
    {
        return 1;
    }
}
