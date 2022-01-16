<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use RuntimeException;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class WorkerTest extends TestCase
{
    /**
     * Check normal job execution
     */
    public function testJobExecuted(): void
    {
        $this->executionTimes = 0;
        $message = new Message('simple', '', []);
        $queue = $this->createMock(QueueInterface::class);
        $this->getWorker()->process($message, $queue);

        self::assertEquals(1, $this->executionTimes);
    }

    /**
     * Check job throws exception
     */
    public function testThrowException(): void
    {
        $this->expectException(RuntimeException::class);

        $message = new Message('exceptional', '', []);
        $queue = $this->createMock(QueueInterface::class);
        $this->getWorker()->process($message, $queue);
    }
}
