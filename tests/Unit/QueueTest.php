<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Closure;
use Psr\Log\NullLogger;
use RuntimeException;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Cli\ScopedLoopInterface;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\QueueProducerInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Queue\Stubs\StubLoop;
use Yiisoft\Queue\Tests\TestCase;

use function count;

enum TestQueue: string
{
    case HIGH_PRIORITY = 'high-priority';
}

final class QueueTest extends TestCase
{
    public function testProducerContract(): void
    {
        $queue = $this->createQueue();
        self::assertInstanceOf(QueueProducerInterface::class, $queue);
        self::assertFalse(method_exists(QueueProducerInterface::class, 'run'));
    }

    public function testPushAndStatus(): void
    {
        $adapter = new InMemoryAdapter();
        $queue = $this->createQueue($adapter);
        $envelope = $queue->push(new GenericMessage('simple', null));
        self::assertSame(1, count($adapter->getMessagesList()));
        /** @var int|string $id */
        $id = $envelope->getMeta()[IdEnvelope::META_ID];
        self::assertSame(MessageStatus::WAITING, $queue->status($id));
    }

    public function testSynchronousProducerProcessesMessage(): void
    {
        $queue = $this->createQueue();
        $queue->push(new GenericMessage('simple', null));
        self::assertSame(1, $this->executionTimes);
        self::assertSame(MessageStatus::NOT_FOUND, $queue->status('1'));
    }

    public function testConsumerContractAndRun(): void
    {
        $adapter = new InMemoryAdapter();
        $producer = $this->createQueue($adapter);
        $producer->push(new GenericMessage('simple', null));
        $consumer = new QueueConsumer($this->getWorker(), $this->getLoop(), new NullLogger(), $adapter);
        self::assertInstanceOf(QueueConsumerInterface::class, $consumer);
        self::assertSame(1, $consumer->run());
        self::assertSame(1, $this->executionTimes);
    }

    public function testSynchronousConsumerIsNoOp(): void
    {
        $consumer = new QueueConsumer($this->getWorker(), $this->getLoop(), new NullLogger());
        self::assertSame(0, $consumer->run());
        $consumer->listen();
    }

    public function testProducerNameSupportsEnum(): void
    {
        self::assertSame('high-priority', $this->createQueue(queueName: TestQueue::HIGH_PRIORITY)->getQueueName());
    }

    public function testConsumerStopsAtLimit(): void
    {
        $adapter = new InMemoryAdapter();
        $producer = $this->createQueue($adapter);
        $producer->push(new GenericMessage('simple', null));
        $producer->push(new GenericMessage('simple', null));
        $consumer = new QueueConsumer($this->getWorker(), $this->getLoop(), new NullLogger(), $adapter);
        self::assertSame(1, $consumer->run(1));
        self::assertSame(1, $this->executionTimes);
        self::assertCount(1, $adapter->getMessagesList(), 'The message that hits the limit stays in the queue.');
    }

    public function testMessageIsNotConsumedWhenLoopCannotContinue(): void
    {
        $adapter = new InMemoryAdapter();
        $producer = $this->createQueue($adapter);
        $producer->push(new GenericMessage('simple', null));
        $producer->push(new GenericMessage('simple', null));
        $consumer = new QueueConsumer($this->getWorker(), new StubLoop(false), new NullLogger(), $adapter);

        self::assertSame(0, $consumer->run());
        self::assertSame(0, $this->executionTimes);
        self::assertCount(2, $adapter->getMessagesList());
    }

    public function testMessageHandledBeforeStopIsNotHandledAgain(): void
    {
        $adapter = new InMemoryAdapter();
        $producer = $this->createQueue($adapter);
        $producer->push(new GenericMessage('simple', null));
        $producer->push(new GenericMessage('simple', null));
        $loop = $this->createMock(LoopInterface::class);
        $loop->method('canContinue')->willReturn(true, false);
        $consumer = new QueueConsumer($this->getWorker(), $loop, new NullLogger(), $adapter);

        self::assertSame(1, $consumer->run());
        self::assertSame(1, $this->executionTimes);
        self::assertCount(1, $adapter->getMessagesList());

        $consumer = new QueueConsumer($this->getWorker(), new StubLoop(), new NullLogger(), $adapter);

        self::assertSame(1, $consumer->run());
        self::assertSame(2, $this->executionTimes, 'The message handled before the stop is not handled again.');
        self::assertCount(0, $adapter->getMessagesList());
    }

    public function testConsumerRunStartsAndFinishesScopedLoop(): void
    {
        $adapter = new InMemoryAdapter();
        $this->createQueue($adapter)->push(new GenericMessage('simple', null));
        $loop = new RecordingScopedLoop();
        $consumer = new QueueConsumer($this->getWorker(), $loop, new NullLogger(), $adapter);

        $consumer->run();

        self::assertSame(['start', 'canContinue', 'finish'], $loop->calls);
    }

    public function testConsumerListenStartsAndFinishesScopedLoop(): void
    {
        $adapter = new InMemoryAdapter();
        $this->createQueue($adapter)->push(new GenericMessage('simple', null));
        $loop = new RecordingScopedLoop();
        $consumer = new QueueConsumer($this->getWorker(), $loop, new NullLogger(), $adapter);

        $consumer->listen();

        self::assertSame(['start', 'canContinue', 'finish'], $loop->calls);
    }

    public function testConsumerFinishesScopedLoopWhenAdapterThrows(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->method('runExisting')->willThrowException(new RuntimeException('Adapter failed.'));
        $loop = new RecordingScopedLoop();
        $consumer = new QueueConsumer($this->getWorker(), $loop, new NullLogger(), $adapter);

        try {
            $consumer->run();
        } catch (RuntimeException) {
        }

        self::assertSame(['start', 'finish'], $loop->calls);
    }

    public function testConsumerListenReportsMessageProcessedBeforeLoopStops(): void
    {
        $adapter = new InMemoryAdapter();
        $producer = $this->createQueue($adapter);
        $message = IdEnvelope::fromMessage($producer->push(new GenericMessage('simple', null)));
        $consumer = new QueueConsumer($this->getWorker(), $this->createStopAfterFirstExecutionLoop(), new NullLogger(), $adapter);

        $consumer->listen();

        self::assertSame(1, $this->executionTimes);
        self::assertSame(MessageStatus::DONE, $adapter->status($message->getId()));
    }

    public function testConsumerListenStopsBeforeProcessingAnotherMessage(): void
    {
        $adapter = new InMemoryAdapter();
        $producer = $this->createQueue($adapter);
        $first = IdEnvelope::fromMessage($producer->push(new GenericMessage('simple', null)));
        $second = IdEnvelope::fromMessage($producer->push(new GenericMessage('simple', null)));
        $consumer = new QueueConsumer($this->getWorker(), $this->createStopAfterFirstExecutionLoop(), new NullLogger(), $adapter);

        $consumer->listen();

        self::assertSame(1, $this->executionTimes);
        self::assertSame(MessageStatus::DONE, $adapter->status($first->getId()));
        self::assertSame(MessageStatus::WAITING, $adapter->status($second->getId()));
    }

    private function createStopAfterFirstExecutionLoop(): LoopInterface
    {
        return new class (fn(): int => $this->executionTimes) implements LoopInterface {
            /**
             * @param Closure(): int $executionTimes
             */
            public function __construct(private readonly Closure $executionTimes) {}

            public function canContinue(): bool
            {
                return ($this->executionTimes)() === 0;
            }
        };
    }
}

final class RecordingScopedLoop implements ScopedLoopInterface
{
    /**
     * @var string[]
     */
    public array $calls = [];

    public function start(): void
    {
        $this->calls[] = 'start';
    }

    public function finish(): void
    {
        $this->calls[] = 'finish';
    }

    public function canContinue(): bool
    {
        $this->calls[] = 'canContinue';
        return true;
    }
}
