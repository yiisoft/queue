<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use RuntimeException;
use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;

class QueueHandler
{
    private int $jobExecutionTimes = 0;

    public function getJobExecutionTimes(): int
    {
        return $this->jobExecutionTimes;
    }

    public function simple(MessageInterface $message): void
    {
        $this->jobExecutionTimes++;
    }

    public function exceptional(MessageInterface $message): void
    {
        $this->jobExecutionTimes++;
        throw new RuntimeException('Test exception');
    }

    public function retryable(MessageInterface $message): void
    {
        if ($message->getPayloadMeta()[PayloadInterface::META_KEY_ATTEMPTS] > 1) {
            throw new RuntimeException('Test exception');
        }

        $this->jobExecutionTimes++;
    }
}
