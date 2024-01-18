<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Support;

use Yiisoft\Queue\Enum\JobStatus;

class TestJobStatus extends JobStatus
{
    public static function withStatus(int $status): self
    {
        return new self($status);
    }
}
