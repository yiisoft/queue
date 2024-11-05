<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Adapter\StubAdapter;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Queue;
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
        $this->assertSame(Queue::DEFAULT_CHANNEL_NAME, $queue->getChannelName());
        $queue->listen();
    }

    public function testWithAdapter(): void
    {
        $queue = new StubQueue();

        $this->assertNotSame($queue, $queue->withAdapter(new StubAdapter()));
    }

    public function testWithChannelName(): void
    {
        $sourceQueue = new StubQueue();

        $queue = $sourceQueue->withChannelName('test');

        $this->assertNotSame($queue, $sourceQueue);
        $this->assertSame(Queue::DEFAULT_CHANNEL_NAME, $sourceQueue->getChannelName());
        $this->assertSame('test', $queue->getChannelName());
    }
}
