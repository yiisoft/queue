<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueDecorator;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;

final class QueueDecoratorTest extends TestCase
{
    public function testStatus(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $messageStatus = MessageStatus::WAITING;
        $queue->expects($this->once())->method('status')->willReturn($messageStatus);
        $collector = new QueueCollector();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $result = $decorator->status('');
        $this->assertEquals($messageStatus, $result);
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

        $decorator->push($message);
    }

    public function testPushCollectsCallLocation(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $queue = $this->createMock(QueueInterface::class);
        $queue->method('getName')->willReturn('test-queue');
        $queue->method('push')->willReturn($message);
        $collector = new QueueCollector();
        $collector->startup();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $line = __LINE__ + 1;
        $decorator->push($message);

        $collected = $collector->getCollected();
        $this->assertSame(
            ['message' => $message, 'line' => __FILE__ . ':' . $line],
            $collected['pushes']['test-queue'][0],
        );
    }

    public function testStatusCollectsCallLocation(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->method('status')->willReturn(MessageStatus::WAITING);
        $collector = new QueueCollector();
        $collector->startup();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $line = __LINE__ + 1;
        $decorator->status('42');

        $collected = $collector->getCollected();
        $this->assertSame(
            ['id' => '42', 'status' => MessageStatus::WAITING->key(), 'line' => __FILE__ . ':' . $line],
            $collected['statuses'][0],
        );
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

    public function testWithMiddlewares(): void
    {
        $newQueue = $this->createMock(QueueInterface::class);
        $newQueue->method('getName')->willReturn('new');
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('withMiddlewares')
            ->with('m1', 'm2')
            ->willReturn($newQueue);
        $collector = new QueueCollector();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $result = $decorator->withMiddlewares('m1', 'm2');

        $this->assertInstanceOf(QueueDecorator::class, $result);
        $this->assertNotSame($decorator, $result);
        $this->assertSame('new', $result->getName());
    }

    public function testWithMiddlewaresAdded(): void
    {
        $newQueue = $this->createMock(QueueInterface::class);
        $newQueue->method('getName')->willReturn('new');
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('withMiddlewaresAdded')
            ->with('m1', 'm2')
            ->willReturn($newQueue);
        $collector = new QueueCollector();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $result = $decorator->withMiddlewaresAdded('m1', 'm2');

        $this->assertInstanceOf(QueueDecorator::class, $result);
        $this->assertNotSame($decorator, $result);
        $this->assertSame('new', $result->getName());
    }
}
