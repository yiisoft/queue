<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use RuntimeException;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;

class QueueHandler
{
    private int $jobExecutionTimes = 0;
    private DriverInterface $driver;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function getJobExecutionTimes(): int
    {
        return $this->jobExecutionTimes;
    }

    public function simple(): void
    {
        $this->jobExecutionTimes++;
    }

    public function exceptional(): void
    {
        $this->jobExecutionTimes++;
        throw new RuntimeException('Test exception');
    }

    public function retryable(MessageInterface $message): void
    {
        if ($message->getPayloadMeta()[PayloadInterface::META_KEY_ATTEMPTS] > 0) {
            throw new RuntimeException('Test exception');
        }

        $this->jobExecutionTimes++;
    }

    public function notSupported(): void
    {
        throw new PayloadNotSupportedException($this->driver, new RetryablePayload());
    }
}
