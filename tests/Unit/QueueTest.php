<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Psr\Log\NullLogger;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\QueueProducerInterface;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
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
        self::assertSame('high-priority', $this->createQueue(name: TestQueue::HIGH_PRIORITY)->getName());
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
    }
}
