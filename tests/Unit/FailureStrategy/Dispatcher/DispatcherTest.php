<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\FailureStrategy\Dispatcher;

use RuntimeException;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\FailureStrategy\Dispatcher\Dispatcher;
use Yiisoft\Yii\Queue\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\TestCase;

class DispatcherTest extends TestCase
{
    public function testEventHandled(): void
    {
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::once())->method('handle')->willReturn(true);
        $dispatcher = new Dispatcher($pipeline);
        $event = $this->createEvent();

        $dispatcher->handle($event);

        self::assertFalse($event->shouldThrowException());
    }

    public function testEventNotHandled(): void
    {
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::once())->method('handle')->willReturn(false);
        $dispatcher = new Dispatcher($pipeline);
        $event = $this->createEvent();

        $dispatcher->handle($event);

        self::assertTrue($event->shouldThrowException());
    }

    private function createEvent(): JobFailure
    {
        $queue = $this->createMock(Queue::class);
        $message = new Message('test', null, []);
        $exception = new RuntimeException();

        return new JobFailure($queue, $message, $exception);
    }
}
