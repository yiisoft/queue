<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Adapter\StubAdapter;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\StubQueue;

final class StubQueueTest extends TestCase
{
    public function testBase(): void
    {
        $queue = new StubQueue();
        $message = new Message('test', 42);

        $this->assertSame($message, $queue->push($message));
        $this->assertSame(0, $queue->run());
        $this->assertTrue($queue->status('test')->isDone());
        $this->assertSame(QueueInterface::DEFAULT_CHANNEL_NAME, $queue->getChannelName());
        $this->assertNull($queue->getAdapter());
        $queue->listen();
    }

    public function testWithAdapter(): void
    {
        $sourceQueue = new StubQueue();

        $queue = $sourceQueue->withAdapter(new StubAdapter());

        $this->assertNotSame($queue, $sourceQueue);
        $this->assertInstanceOf(StubAdapter::class, $queue->getAdapter());
    }

    public function testWithChannelName(): void
    {
        $sourceQueue = new StubQueue();

        $queue = $sourceQueue->withChannelName('test');

        $this->assertNotSame($queue, $sourceQueue);
        $this->assertSame(QueueInterface::DEFAULT_CHANNEL_NAME, $sourceQueue->getChannelName());
        $this->assertSame('test', $queue->getChannelName());
    }
}
