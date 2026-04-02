<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueDecorator;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;

class QueueDecoratorTest extends TestCase
{
    public function testStatus(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $jobStatus = JobStatus::WAITING;
        $queue->expects($this->once())->method('status')->willReturn($jobStatus);
        $collector = new QueueCollector();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $result = $decorator->status('');
        $this->assertEquals($jobStatus, $result);
    }

    public function testPush(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())->method('push');
        $message = $this->createMock(MessageInterface::class);
        $collector = new QueueCollector();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $decorator->push($message, []);
    }

    public function testRun(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())->method('run');
        $collector = new QueueCollector();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $decorator->run(5);
    }

    public function testListen(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())->method('listen');
        $collector = new QueueCollector();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $decorator->listen();
    }

    public function testGetName(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())->method('getName')->willReturn('hello');
        $collector = new QueueCollector();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $this->assertEquals('hello', $decorator->getName());
    }
}
