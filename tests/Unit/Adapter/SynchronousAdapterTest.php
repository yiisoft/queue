<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Adapter;

use InvalidArgumentException;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Tests\TestCase;

final class SynchronousAdapterTest extends TestCase
{
    public function testNonIntegerId(): void
    {
        $queue = $this
            ->getQueue()
            ->withAdapter($this->getAdapter());
        $message = new Message('simple', null);
        $envelope = $queue->push($message);

        self::assertArrayHasKey(IdEnvelope::MESSAGE_ID_KEY, $envelope->getMetadata());
        $id = $envelope->getMetadata()[IdEnvelope::MESSAGE_ID_KEY];

        $wrongId = "$id ";
        self::assertSame(MessageStatus::WAITING, $queue->status($wrongId));
    }

    public function testIdSetting(): void
    {
        $message = new Message('simple', []);
        $adapter = $this->getAdapter();

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
        $adapter = $this->getAdapter();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This adapter IDs start with 0.');
        $adapter->status('-1');
    }

    public function testStatusNotMessage(): void
    {
        $adapter = $this->getAdapter();
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('There is no message with the given ID.');
        $adapter->status('1');
    }

    protected function needsRealAdapter(): bool
    {
        return true;
    }
}
