<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Stubs;

use LogicException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\Message;
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
        $this->assertSame(JobStatus::DONE, $queue->status('test'));
        $this->assertNull($queue->getAdapter());
        $queue->listen();
    }

    public function testGetChannelWithoutAdapter(): void
    {
        $queue = new StubQueue();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Adapter is not set.');
        $queue->getChannel();
    }

    public function testWithAdapter(): void
    {
        $sourceQueue = new StubQueue();

        $queue = $sourceQueue->withAdapter(new StubAdapter());

        $this->assertNotSame($queue, $sourceQueue);
        $this->assertInstanceOf(StubAdapter::class, $queue->getAdapter());
    }
}
