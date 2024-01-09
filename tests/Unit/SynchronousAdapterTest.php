<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\QueueFactory;
use Yiisoft\Queue\Tests\TestCase;

final class SynchronousAdapterTest extends TestCase
{
    protected function needsRealAdapter(): bool
    {
        return true;
    }

    public function testNonIntegerId(): void
    {
        $queue = $this
            ->getQueue()
            ->withAdapter($this->getAdapter());
        $message = new Message('simple', null);
        $queue->push($message);
        $id = $message->getId();
        $wrongId = "$id ";
        self::assertEquals(JobStatus::waiting(), $queue->status($wrongId));
    }

    public function testIdSetting(): void
    {
        $message = new Message('simple', []);
        $adapter = $this->getAdapter();

        $ids = [];
        $adapter->push($message);
        $ids[] = $message->getId();
        $adapter->push($message);
        $ids[] = $message->getId();
        $adapter->push($message);
        $ids[] = $message->getId();

        self::assertCount(3, array_unique($ids));
    }

    public function testWithSameChannel(): void
    {
        $adapter = $this->getAdapter();
        self::assertEquals($adapter, $adapter->withChannel(QueueFactory::DEFAULT_CHANNEL_NAME));
    }

    public function testWithAnotherChannel(): void
    {
        $adapter = $this->getAdapter();
        $adapter->push(new Message('test', null));
        $adapterNew = $adapter->withChannel('test');

        self::assertNotEquals($adapter, $adapterNew);

        $executed = false;
        $adapterNew->runExisting(function () use (&$executed) {
            $executed = true;
        });

        self::assertFalse($executed);

        $executed = false;
        $adapter->runExisting(function () use (&$executed) {
            $executed = true;
        });

        self::assertTrue($executed);
    }

    public function testStatusIdLessZero(): void
    {
        $adapter = $this->getAdapter();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('This adapter IDs start with 0.');
        $adapter->status('-1');
    }

    public function testStatusNotMessage(): void
    {
        $adapter = $this->getAdapter();
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('There is no message with the given ID.');
        $adapter->status('1');
    }
}
