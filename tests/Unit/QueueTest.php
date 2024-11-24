<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\Exception\AdapterConfiguration\AdapterNotConfiguredException;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Tests\App\FakeAdapter;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;

final class QueueTest extends TestCase
{
    private bool $needsRealAdapter = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->needsRealAdapter = true;
    }

    protected function needsRealAdapter(): bool
    {
        return $this->needsRealAdapter;
    }

    public function testPushSuccessful(): void
    {
        $adapter = new FakeAdapter();
        $queue = $this
            ->getQueue()
            ->withAdapter($adapter);
        $message = new Message('simple', null);
        $queue->push($message);

        self::assertSame([$message], $adapter->pushMessages);
    }

    public function testRun(): void
    {
        $queue = $this
            ->getQueue()
            ->withAdapter($this->getAdapter());
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
        $queue = $this
            ->getQueue()
            ->withAdapter($this->getAdapter());
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->run(1);

        self::assertEquals(1, $this->executionTimes);
    }

    public function testListen(): void
    {
        $queue = $this
            ->getQueue()
            ->withAdapter($this->getAdapter());
        $message = new Message('simple', null);
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->listen();

        self::assertEquals(2, $this->executionTimes);
    }

    public function testStatus(): void
    {
        $queue = $this
            ->getQueue()
            ->withAdapter($this->getAdapter());
        $message = new Message('simple', null);
        $envelope = $queue->push($message);

        self::assertArrayHasKey(IdEnvelope::MESSAGE_ID_KEY, $envelope->getMetadata());
        /**
         * @var int|string $id
         */
        $id = $envelope->getMetadata()[IdEnvelope::MESSAGE_ID_KEY];

        $status = $queue->status($id);
        self::assertTrue($status->isWaiting());

        $queue->run();
        $status = $queue->status($id);
        self::assertTrue($status->isDone());
    }

    public function testAdapterNotConfiguredException(): void
    {
        try {
            $queue = $this->getQueue();
            $message = new Message('simple', null);
            $envelope = $queue->push($message);
            $queue->status($envelope->getMetadata()[IdEnvelope::MESSAGE_ID_KEY]);
        } catch (AdapterNotConfiguredException $exception) {
            self::assertSame($exception::class, AdapterNotConfiguredException::class);
            self::assertSame($exception->getName(), 'Adapter is not configured');
            $this->assertMatchesRegularExpression('/withAdapter/', $exception->getSolution());
        }
    }

    public function testAdapterNotConfiguredExceptionForRun(): void
    {
        try {
            $this->getQueue()->run();
        } catch (AdapterNotConfiguredException $exception) {
            self::assertSame($exception::class, AdapterNotConfiguredException::class);
            self::assertSame($exception->getName(), 'Adapter is not configured');
            $this->assertMatchesRegularExpression('/withAdapter/', $exception->getSolution());
        }
    }

    public function testRunWithSignalLoop(): void
    {
        if (!extension_loaded('pcntl')) {
            self::markTestSkipped('PCNTL support required');
        }

        $this->loop = new SignalLoop();
        $queue = $this
            ->getQueue()
            ->withAdapter($this->getAdapter());
        $message = new Message('simple', null);
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->run();

        self::assertEquals(2, $this->executionTimes);
    }
}
