<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use BadMethodCallException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Queue;
use Yiisoft\Queue\Tests\App\FakeAdapter;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Worker\WorkerInterface;

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
        self::assertSame(MessageStatus::WAITING, $status);

        $queue->run();
        $status = $queue->status($id);
        self::assertSame(MessageStatus::DONE, $status);
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

    public function testPushWithoutAdapterProcessesInline(): void
    {
        $message = new Message('simple', null);
        $worker = $this->createMock(WorkerInterface::class);
        $worker->expects(self::once())->method('process')->with($message);

        $queue = new Queue(
            $worker,
            $this->getLoop(),
            new NullLogger(),
            $this->getPushMiddlewareDispatcher(),
        );

        $queue->push($message);
    }

    public function testConstructorWarnsWhenNoAdapter(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        new Queue(
            $this->getWorker(),
            $this->getLoop(),
            $logger,
            $this->getPushMiddlewareDispatcher(),
        );
    }

    public function testRunThrowsWithoutAdapter(): void
    {
        $queue = $this->createQueue();

        $this->expectException(BadMethodCallException::class);
        $queue->run();
    }

    public function testListenThrowsWithoutAdapter(): void
    {
        $queue = $this->createQueue();

        $this->expectException(BadMethodCallException::class);
        $queue->listen();
    }

    public function testStatusThrowsWithoutAdapter(): void
    {
        $queue = $this->createQueue();

        $this->expectException(BadMethodCallException::class);
        $queue->status('1');
    }

    protected function needsRealAdapter(): bool
    {
        return true;
    }
}
