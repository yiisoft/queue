<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use BadMethodCallException;
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Tests\App\FakeAdapter;
use Yiisoft\Queue\Tests\App\InMemoryAdapter;
use Yiisoft\Queue\Tests\TestCase;

use function extension_loaded;

// Test enum for BackedEnum testing
enum TestQueue: string
{
    case DEFAULT = 'default';
    case HIGH_PRIORITY = 'high-priority';
}

final class QueueTest extends TestCase
{
    public function testPushSuccessful(): void
    {
        $adapter = new FakeAdapter();
        $queue = $this->createQueue($adapter);
        $message = new Message('simple', null);
        $queue->push($message);

        self::assertSame([$message], $adapter->pushMessages);
    }

    public function testPushSynchronouslyProcessesMessage(): void
    {
        $queue = $this->createQueue();
        $message = new Message('simple', null);

        $queue->push($message);
        $queue->push(clone $message);

        self::assertSame(2, $this->executionTimes);
    }

    public function testRunWithoutAdapterReturnsZero(): void
    {
        $queue = $this->createQueue();
        $message = new Message('simple', null);
        $queue->push($message);
        $queue->push(clone $message);

        self::assertSame(0, $queue->run());
        self::assertSame(2, $this->executionTimes);
    }

    public function testListenThrowsWithoutAdapter(): void
    {
        $queue = $this->createQueue();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot listen without an adapter. Queue is in synchronous mode.');
        $queue->listen();
    }

    public function testStatusThrowsWithoutAdapter(): void
    {
        $queue = $this->createQueue();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Cannot get message status without an adapter. Queue is in synchronous mode.');
        $queue->status('1');
    }

    public function testRunWithAdapter(): void
    {
        $queue = $this->createQueue(new InMemoryAdapter());
        $message = new Message('simple', null);
        $queue->push($message);
        $queue->push(clone $message);

        self::assertSame(2, $queue->run());
        self::assertSame(2, $this->executionTimes);
    }

    public function testRunPartlyWithAdapter(): void
    {
        $queue = $this->createQueue(new InMemoryAdapter());
        $message = new Message('simple', null);
        $queue->push($message);
        $queue->push(clone $message);

        self::assertSame(1, $queue->run(1));
        self::assertSame(1, $this->executionTimes);
    }

    public function testListenWithAdapter(): void
    {
        $queue = $this->createQueue(new InMemoryAdapter());
        $message = new Message('simple', null);
        $queue->push($message);
        $queue->push(clone $message);

        $queue->listen();

        self::assertSame(2, $this->executionTimes);
    }

    public function testStatusWithAdapter(): void
    {
        $queue = $this->createQueue(new InMemoryAdapter());
        $envelope = $queue->push(new Message('simple', null));

        self::assertArrayHasKey(IdEnvelope::MESSAGE_ID_KEY, $envelope->getMetadata());
        /** @var int|string $id */
        $id = $envelope->getMetadata()[IdEnvelope::MESSAGE_ID_KEY];

        self::assertSame(MessageStatus::WAITING, $queue->status($id));

        $queue->run();
        self::assertSame(MessageStatus::DONE, $queue->status($id));
    }

    public function testRunWithSignalLoop(): void
    {
        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped('This rest requires PCNTL extension');
        }

        $this->loop = new SignalLoop();
        $queue = $this->createQueue();
        $message = new Message('simple', null);
        $queue->push($message);
        $queue->push(clone $message);

        self::assertSame(0, $queue->run());
        self::assertSame(2, $this->executionTimes);
    }

    public function testGetName(): void
    {
        $queue = $this->createQueue(name: 'test-queue');

        $this->assertSame('test-queue', $queue->getName());
    }

    public function testGetNameWithBackedEnum(): void
    {
        $queue = $this->createQueue(name: TestQueue::HIGH_PRIORITY);

        $this->assertSame('high-priority', $queue->getName());
    }
}
