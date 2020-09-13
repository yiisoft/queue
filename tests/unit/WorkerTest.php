<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use RuntimeException;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Queue;
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
        $queue = $this->createMock(Queue::class);
        $this->getWorker()->process($message, $queue);

        $this->assertEquals(1, $this->executionTimes);
    }

    /**
     * Check job execution is prevented
     */
    public function testJobNotExecuted(): void
    {
        $handler = static function ($event) {
            if ($event instanceof BeforeExecution) {
                $event->stopExecution();
            }
        };
        $this->setEventHandlers($handler);

        $message = new Message('simple', '', []);
        $queue = $this->createMock(Queue::class);
        $this->getWorker()->process($message, $queue);

        $this->assertEquals(0, $this->executionTimes);
    }

    /**
     * Check job throws exception
     */
    public function testThrowException(): void
    {
        $this->expectException(RuntimeException::class);

        $message = new Message('exceptional', '', []);
        $queue = $this->createMock(Queue::class);
        $this->getWorker()->process($message, $queue);
    }

    /**
     * Check exception throwing is prevented
     */
    public function testThrowExceptionPrevented(): void
    {
        $handler = static function ($event) {
            if ($event instanceof JobFailure) {
                $event->preventThrowing();
            }
        };
        $this->setEventHandlers($handler);

        $message = new Message('exceptional', '', []);
        $queue = $this->createMock(Queue::class);
        $this->getWorker()->process($message, $queue);

        $this->assertEquals(1, $this->executionTimes);
    }
}
