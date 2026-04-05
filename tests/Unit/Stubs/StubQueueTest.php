<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Stubs;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Stubs\StubQueue;

final class StubQueueTest extends TestCase
{
    public function testBase(): void
    {
        $queue = new StubQueue();
        $message = new Message('test', 42);

        $this->assertSame($message, $queue->push($message));
        $this->assertSame(0, $queue->run());
        $this->assertSame(JobStatus::DONE, $queue->status('test'));
        $queue->listen();
    }
}
