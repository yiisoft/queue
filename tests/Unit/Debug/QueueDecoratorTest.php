<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueDecorator;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\App\FakeAdapter;

class QueueDecoratorTest extends TestCase
{
    public function testWithAdapter(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $collector = new QueueCollector();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $queueAdapter = $this->createMock(AdapterInterface::class);

        $newDecorator = $decorator->withAdapter($queueAdapter);

        $this->assertInstanceOf(QueueDecorator::class, $newDecorator);
    }

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

    public function testImmutable(): void
    {
        $queueDecorator = new QueueDecorator(
            $this->createMock(QueueInterface::class),
            new QueueCollector(),
        );
        $this->assertNotSame($queueDecorator, $queueDecorator->withAdapter(new FakeAdapter()));
    }
}
