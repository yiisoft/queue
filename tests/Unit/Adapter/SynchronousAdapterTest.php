<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Adapter;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Adapter\SynchronousAdapter;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Stubs\StubQueue;
use Yiisoft\Queue\Stubs\StubWorker;

final class SynchronousAdapterTest extends TestCase
{
    public function testNonIntegerId(): void
    {
        $adapter = new SynchronousAdapter(new StubWorker(), new StubQueue());
        $message = new Message('simple', null);
        $envelope = $adapter->push($message);

        self::assertArrayHasKey(IdEnvelope::MESSAGE_ID_KEY, $envelope->getMetadata());
        $id = $envelope->getMetadata()[IdEnvelope::MESSAGE_ID_KEY];

        $wrongId = "$id ";
        self::assertSame(MessageStatus::WAITING, $adapter->status($wrongId));
    }

    public function testIdSetting(): void
    {
        $message = new Message('simple', []);
        $adapter = new SynchronousAdapter(new StubWorker(), new StubQueue());

        $ids = [];
        $envelope = $adapter->push($message);
        $ids[] = $envelope->getMetadata()[IdEnvelope::MESSAGE_ID_KEY];
        $envelope = $adapter->push($message);
        $ids[] = $envelope->getMetadata()[IdEnvelope::MESSAGE_ID_KEY];
        $envelope = $adapter->push($message);
        $ids[] = $envelope->getMetadata()[IdEnvelope::MESSAGE_ID_KEY];

        self::assertCount(3, array_unique($ids));
    }

    public function testStatusIdLessZero(): void
    {
        $adapter = new SynchronousAdapter(new StubWorker(), new StubQueue());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This adapter IDs start with 0.');
        $adapter->status('-1');
    }

    public function testStatusNotMessage(): void
    {
        $adapter = new SynchronousAdapter(new StubWorker(), new StubQueue());

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('There is no message with the given ID.');
        $adapter->status('1');
    }
}
