<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Stubs;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Stubs\StubQueue;
use Yiisoft\Queue\Stubs\StubAdapter;

final class StubQueueTest extends TestCase
{
    public function testBase(): void
    {
        $queue = new StubQueue();
        $message = new Message('test', 42);

        $this->assertSame($message, $queue->push($message));
        $this->assertSame(0, $queue->run());
        $this->assertTrue($queue->status('test')->isDone());
        $this->assertNull($queue->getChannelName());
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
}
