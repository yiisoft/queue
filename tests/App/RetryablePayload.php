<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use RuntimeException;
use Yiisoft\Yii\Queue\Payload\AttemptsRestrictedPayloadInterface;

class RetryablePayload extends SimplePayload implements AttemptsRestrictedPayloadInterface
{
    public function getAttempts(): int
    {
        return 2;
    }

    public function getName(): string
    {
        return 'retryable';
    }
}
