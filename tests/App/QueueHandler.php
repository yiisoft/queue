<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use RuntimeException;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Driver\SynchronousDriver;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;
use Yiisoft\Yii\Queue\Queue;

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

    public function notSupported(MessageInterface $message): void
    {
        throw new PayloadNotSupportedException($this->driver, new RetryablePayload());
    }
}
