<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Psr\Log\LoggerInterface;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Queue\Exception\JobFailureException;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Middleware\MiddlewareFactoryInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\App\FakeHandler;
use Yiisoft\Queue\Tests\Support\ExceptionMessage;
use Yiisoft\Queue\Tests\Support\ExceptionMessageHandler;
use Yiisoft\Queue\Tests\Support\StackMessage;
use Yiisoft\Queue\Tests\Support\StackMessageHandler;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Test\Support\Log\SimpleLogger;

final class WorkerTest extends TestCase
{
    public function testJobExecutedWithDefinitionClassHandler(): void
    {
        $message = new Message('data', ['test-meta-data']);

        $handler = new FakeHandler();

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams(new SimpleLogger(), [Message::class => $handler]);

        $worker->process($message, $queue);

        $this->assertSame([$message], $handler::$processedMessages);
    }

    public function testHandlerIsReplacedWithEnvelopsOne(): void
    {
        $message = new StackMessage(['test-data']);

        $stackMessageHandler = new StackMessageHandler();

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams(
            new SimpleLogger(),
            [StackMessage::class => fn ($message) => $stackMessageHandler->handle($message)]
        );

        $worker->process($message, $queue);
        $this->assertSame([$message], $stackMessageHandler->processedMessages);
    }

    public function testJobFailWithDefinitionHandlerException(): void
    {
        $message = new ExceptionMessage(['test-data']);
        $logger = new SimpleLogger();

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams(
            $logger,
            [ExceptionMessage::class => fn ($message) => (new ExceptionMessageHandler())->handle($message)]
        );

        $this->expectException(JobFailureException::class);
        $this->expectExceptionMessage(
            "Processing of message #null is stopped because of an exception:\nTest exception."
        );
        $worker->process($message, $queue);
    }

    private function createWorkerByParams(
        LoggerInterface $logger,
        array $listeners,
    ): Worker {
        $collection = (new ListenerCollection());
        foreach ($listeners as $class => $listener) {
            $collection = $collection->add($listener, $class);
        }
        return new Worker(
            $logger,
            new Dispatcher(new Provider($collection)),
            $this->createContainer(),
            new MiddlewareDispatcher($this->createMock(MiddlewareFactoryInterface::class)),
            new MiddlewareDispatcher($this->createMock(MiddlewareFactoryInterface::class)),
        );
    }
}
