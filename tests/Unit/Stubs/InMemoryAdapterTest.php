<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Stubs;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Stubs\InMemoryAdapter;

final class InMemoryAdapterTest extends TestCase
{
    public function testPush(): void
    {
        $adapter = new InMemoryAdapter();

        $envelope1 = $adapter->push(new Message('test', 'a'));
        $envelope2 = $adapter->push(new Message('test', 'b'));
        $envelope3 = $adapter->push(new Message('test', 'c'));

        $this->assertInstanceOf(IdEnvelope::class, $envelope1);
        $this->assertInstanceOf(IdEnvelope::class, $envelope2);
        $this->assertInstanceOf(IdEnvelope::class, $envelope3);

        $this->assertSame(0, $envelope1->getId());
        $this->assertSame('a', $envelope1->getMessage()->getData());
        $this->assertSame(1, $envelope2->getId());
        $this->assertSame('b', $envelope2->getMessage()->getData());
        $this->assertSame(2, $envelope3->getId());
        $this->assertSame('c', $envelope3->getMessage()->getData());
    }

    public function testStatusWaitingForPushedMessage(): void
    {
        $adapter = new InMemoryAdapter();
        $envelope = $adapter->push(new Message('test', null));

        $this->assertInstanceOf(IdEnvelope::class, $envelope);
        $this->assertSame(MessageStatus::WAITING, $adapter->status($envelope->getId()));
    }

    public function testStatusDoneAfterProcessing(): void
    {
        $adapter = new InMemoryAdapter();
        $envelope = $adapter->push(new Message('test', null));

        $adapter->runExisting(static fn() => true);

        $this->assertInstanceOf(IdEnvelope::class, $envelope);
        $this->assertSame(MessageStatus::DONE, $adapter->status($envelope->getId()));
    }

    public function testStatusNotFoundForNonExistentId(): void
    {
        $adapter = new InMemoryAdapter();

        $this->assertSame(MessageStatus::NOT_FOUND, $adapter->status(99));
    }

    public function testStatusNotFoundForNegativeId(): void
    {
        $adapter = new InMemoryAdapter();

        $this->assertSame(MessageStatus::NOT_FOUND, $adapter->status(-1));
    }

    public function testStatusAcceptsStringId(): void
    {
        $adapter = new InMemoryAdapter();
        $envelope = $adapter->push(new Message('test', null));

        $this->assertInstanceOf(IdEnvelope::class, $envelope);
        $this->assertSame(MessageStatus::WAITING, $adapter->status((string) $envelope->getId()));
    }

    public function testRunExistingProcessesAllMessages(): void
    {
        $adapter = new InMemoryAdapter();
        $adapter->push(new Message('test', 'a'));
        $adapter->push(new Message('test', 'b'));
        $adapter->push(new Message('test', 'c'));

        $processed = [];
        $adapter->runExisting(
            static function (MessageInterface $message) use (&$processed): bool {
                $processed[] = $message->getData();
                return true;
            }
        );

        $this->assertSame(['a', 'b', 'c'], $processed);
    }

    public function testRunExistingStopsWhenHandlerReturnsFalse(): void
    {
        $adapter = new InMemoryAdapter();
        $adapter->push(new Message('test', 'a'));
        $adapter->push(new Message('test', 'b'));
        $adapter->push(new Message('test', 'c'));

        $processed = [];
        $adapter->runExisting(
            static function (MessageInterface $message) use (&$processed): bool {
                $processed[] = $message->getData();
                return false;
            }
        );

        $this->assertSame(['a'], $processed);
    }

    public function testRunExistingOnEmptyQueue(): void
    {
        $adapter = new InMemoryAdapter();

        $called = false;
        $adapter->runExisting(static function () use (&$called): bool {
            $called = true;
            return true;
        });

        $this->assertFalse($called);
    }

    public function testRunExistingDoesNotReprocessMessages(): void
    {
        $adapter = new InMemoryAdapter();
        $adapter->push(new Message('test', 'x'));

        $count = 0;
        $handler = static function () use (&$count): bool {
            $count++;
            return true;
        };
        $adapter->runExisting($handler);
        $adapter->runExisting($handler);

        $this->assertSame(1, $count);
    }

    public function testIdContinuesAfterProcessing(): void
    {
        $adapter = new InMemoryAdapter();
        $adapter->push(new Message('test', null));
        $adapter->runExisting(static fn() => true);

        $envelope = $adapter->push(new Message('test', null));

        $this->assertInstanceOf(IdEnvelope::class, $envelope);
        $this->assertSame(1, $envelope->getId());
        $this->assertSame(MessageStatus::WAITING, $adapter->status($envelope->getId()));
    }

    public function testSubscribeProcessesExistingMessages(): void
    {
        $adapter = new InMemoryAdapter();
        $adapter->push(new Message('test', 'a'));
        $adapter->push(new Message('test', 'b'));

        $processed = [];
        $adapter->subscribe(
            static function (MessageInterface $message) use (&$processed): bool {
                $processed[] = $message->getData();
                return true;
            }
        );

        $this->assertSame(['a', 'b'], $processed);
    }
}
