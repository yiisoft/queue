<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Debug\QueueCollector;
use Yiisoft\Yii\Queue\Debug\QueueDecorator;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\App\FakeAdapter;
use Yiisoft\Yii\Queue\Tests\Unit\Support\TestJobStatus;

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
        $jobStatus = TestJobStatus::withStatus(1);
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

    public function testGetChannelName(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())->method('getChannelName')->willReturn('getChannelName');
        $collector = new QueueCollector();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $this->assertEquals('getChannelName', $decorator->getChannelName());
    }

    public function testWithChannelName(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())->method('withChannelName')->willReturn($queue);
        $collector = new QueueCollector();
        $decorator = new QueueDecorator(
            $queue,
            $collector,
        );

        $this->assertInstanceOf(QueueInterface::class, $decorator->withChannelName('test'));
    }

    public function testImmutable(): void
    {
        $queueDecorator = new QueueDecorator(
            $this->createMock(QueueInterface::class),
            new QueueCollector()
        );
        $this->assertNotSame($queueDecorator, $queueDecorator->withAdapter(new FakeAdapter()));
        $this->assertNotSame($queueDecorator, $queueDecorator->withChannelName('test'));
    }
}
