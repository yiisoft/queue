<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use RuntimeException;
use Yiisoft\Yii\Queue\Job\AttemptsRestrictedJob;

class RetryableJob extends AttemptsRestrictedJob
{
    public bool $executed = false;

    public function __construct(int $attemptsMax = 2)
    {
        $this->attemptsMax = $attemptsMax;
    }

    public function getTtr(): int
    {
        return 1;
    }

    public function execute(): void
    {
        if ($this->canRetry()) {
            throw new RuntimeException('Test exception');
        }

        $this->executed = true;
    }
}
