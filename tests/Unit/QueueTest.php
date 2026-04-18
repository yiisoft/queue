<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Tests\App\FakeAdapter;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;

use function extension_loaded;

// Test enum for BackedEnum testing
enum TestQueue: string
{
    case DEFAULT = 'default';
    case HIGH_PRIORITY = 'high-priority';
}

final class QueueTest extends TestCase
{
    private bool $needsRealAdapter = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->needsRealAdapter = true;
    }

    public function testPushSuccessful(): void
    {
        $adapter = new FakeAdapter();
        $queue = $this->createQueue($adapter);
        $message = new Message('simple', null);
        $queue->push($message);

        self::assertSame([$message], $adapter->pushMessages);
    }

    public function testRun(): void
    {
        $queue = $this->createQueue($this->getAdapter());
        $message = new Message('simple', null);
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->run();

        self::assertEquals(2, $this->executionTimes);
    }

    public function testRunPartly(): void
    {
        $message = new Message('simple', null);
        $queue = $this->createQueue($this->getAdapter());
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->run(1);

        self::assertEquals(1, $this->executionTimes);
    }

    public function testListen(): void
    {
        $queue = $this->createQueue($this->getAdapter());
        $message = new Message('simple', null);
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->listen();

        self::assertEquals(2, $this->executionTimes);
    }

    public function testStatus(): void
    {
        $queue = $this->createQueue($this->getAdapter());
        $message = new Message('simple', null);
        $envelope = $queue->push($message);

        self::assertArrayHasKey(IdEnvelope::MESSAGE_ID_KEY, $envelope->getMetadata());
        /**
         * @var int|string $id
         */
        $id = $envelope->getMetadata()[IdEnvelope::MESSAGE_ID_KEY];

        $status = $queue->status($id);
        self::assertSame(JobStatus::WAITING, $status);

        $queue->run();
        $status = $queue->status($id);
        self::assertSame(JobStatus::DONE, $status);
    }

    public function testRunWithSignalLoop(): void
    {
        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped('This rest requires PCNTL extension');
        }

        $this->loop = new SignalLoop();
        $queue = $this->createQueue($this->getAdapter());
        $message = new Message('simple', null);
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->run();

        self::assertEquals(2, $this->executionTimes);
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

    protected function needsRealAdapter(): bool
    {
        return $this->needsRealAdapter;
    }
}
