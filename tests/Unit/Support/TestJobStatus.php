<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Support;

use Yiisoft\Yii\Queue\Enum\JobStatus;

class TestJobStatus extends JobStatus
{
    public static function withStatus(int $status): self
    {
        return new TestJobStatus($status);
    }
}
