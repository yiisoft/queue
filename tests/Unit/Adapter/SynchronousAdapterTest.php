<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Adapter;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Queue\Adapter\SynchronousAdapter;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Stubs\StubQueue;
use Yiisoft\Queue\Stubs\StubWorker;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Tests\Unit\Support\IntEnum;
use Yiisoft\Queue\Tests\Unit\Support\StringEnum;

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
        $envelope = $queue->push($message);

        self::assertArrayHasKey(IdEnvelope::MESSAGE_ID_KEY, $envelope->getMetadata());
        $id = $envelope->getMetadata()[IdEnvelope::MESSAGE_ID_KEY];

        $wrongId = "$id ";
        self::assertSame(JobStatus::WAITING, $queue->status($wrongId));
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

    public function testWithSameChannel(): void
    {
        $adapter = $this->getAdapter();
        self::assertEquals($adapter, $adapter->withChannel(QueueProviderInterface::DEFAULT_CHANNEL));
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

    public static function dataChannels(): iterable
    {
        yield 'string' => ['test', 'test'];
        yield 'string-enum' => ['red', StringEnum::RED];
        yield 'integer-enum' => ['1', IntEnum::ONE];
    }

    #[DataProvider('dataChannels')]
    public function testWithChannel(string $expected, mixed $channel): void
    {
        $adapter = (new SynchronousAdapter(new StubWorker(), new StubQueue()))->withChannel($channel);

        $this->assertSame($expected, $adapter->getChannel());
    }

    #[DataProvider('dataChannels')]
    public function testChannelInConstructor(string $expected, mixed $channel): void
    {
        $adapter = new SynchronousAdapter(new StubWorker(), new StubQueue(), $channel);

        $this->assertSame($expected, $adapter->getChannel());
    }
}
