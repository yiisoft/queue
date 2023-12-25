<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use Yiisoft\Yii\Queue\Cli\SignalLoop;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\AdapterNotConfiguredException;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Tests\App\FakeAdapter;
use Yiisoft\Yii\Queue\Tests\Support\NullMessageHandler;
use Yiisoft\Yii\Queue\Tests\Support\StackMessageHandler;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class QueueTest extends TestCase
{
    protected function needsRealAdapter(): bool
    {
        return true;
    }

    public function testPushSuccessful(): void
    {
        $adapter = new FakeAdapter();
        $queue = $this
            ->getQueue()
            ->withAdapter($adapter);
        $message = new Message(NullMessageHandler::class, null);
        $queue->push($message);

        self::assertSame([$message], $adapter->pushMessages);
    }

    public function testRun(): void
    {
        $queue = $this
            ->getQueue()
            ->withAdapter($this->getAdapter());
        $message = new Message(StackMessageHandler::class, null);
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->run();

        $stackMessageHandler = $this->container->get(StackMessageHandler::class);
        self::assertCount(2, $stackMessageHandler->processedMessages);
    }

    public function testRunPartly(): void
    {
        $message = new Message(StackMessageHandler::class, null);
        $queue = $this
            ->getQueue()
            ->withAdapter($this->getAdapter());
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->run(1);

        $stackMessageHandler = $this->container->get(StackMessageHandler::class);
        self::assertCount(1, $stackMessageHandler->processedMessages);
    }

    public function testListen(): void
    {
        $queue = $this
            ->getQueue()
            ->withAdapter($this->getAdapter());
        $message = new Message(StackMessageHandler::class, null);
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->listen();

        $stackMessageHandler = $this->container->get(StackMessageHandler::class);
        self::assertCount(2, $stackMessageHandler->processedMessages);
    }

    public function testStatus(): void
    {
        $queue = $this
            ->getQueue()
            ->withAdapter($this->getAdapter());
        $message = new Message(NullMessageHandler::class, null);
        $queue->push($message);
        $id = $message->getId();

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
            $message = new Message(NullMessageHandler::class, null);
            $queue->push($message);
            $queue->status($message->getId());
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
        $this->loop = new SignalLoop();
        $queue = $this
            ->getQueue()
            ->withAdapter($this->getAdapter());
        $message = new Message(StackMessageHandler::class, null);
        $message2 = clone $message;
        $queue->push($message);
        $queue->push($message2);
        $queue->run();

        $stackMessageHandler = $this->container->get(StackMessageHandler::class);
        self::assertCount(2, $stackMessageHandler->processedMessages);
    }
}
