<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Psr\Log\LoggerInterface;
use Yiisoft\EventDispatcher\Dispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\ListenerCollection;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Queue\Exception\JobFailureException;
use Yiisoft\Queue\Message\HandlerEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Middleware\MiddlewareFactoryInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\App\FakeHandler;
use Yiisoft\Queue\Tests\Support\ExceptionMessageHandler;
use Yiisoft\Queue\Tests\Support\StackMessageHandler;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Test\Support\Log\SimpleLogger;

final class WorkerTest extends TestCase
{
    public function testJobExecutedWithDefinitionClassHandler(): void
    {
        $envelope = new HandlerEnvelope(
            $message = new Message(FakeHandler::class, ['test-data']),
            FakeHandler::class,
        );

        $handler = new FakeHandler();

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams(new SimpleLogger(), [Message::class => $handler]);

        $worker->process($envelope, $queue);

        $this->assertSame([$message], $handler::$processedMessages);
    }

    public function testHandlerIsReplacedWithEnvelopsOne(): void
    {
        $envelope = new HandlerEnvelope(
            $message = new Message(['test-data']),
            StackMessageHandler::class,
        );

        $stackMessageHandler = new StackMessageHandler();

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams(
            new SimpleLogger(),
            [Message::class => fn ($message) => $stackMessageHandler->handle($message)]
        );

        $worker->process($envelope, $queue);
        $this->assertSame([$message], $stackMessageHandler->processedMessages);
    }

    public function testJobFailWithDefinitionHandlerException(): void
    {
        $message = new HandlerEnvelope(
            new Message(['test-data']),
            ExceptionMessageHandler::class,
        );
        $logger = new SimpleLogger();

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams(
            $logger,
            [Message::class => fn ($message) => (new ExceptionMessageHandler())->handle($message)]
        );

        try {
            $worker->process($message, $queue);
        } catch (JobFailureException $exception) {
            self::assertSame($exception::class, JobFailureException::class);
            self::assertSame(
                $exception->getMessage(),
                "Processing of message #null is stopped because of an exception:\nTest exception."
            );
            self::assertEquals(['test-data'], $exception->getQueueMessage()->getData());
        } finally {
            $messages = $logger->getMessages();
            $this->assertNotEmpty($messages);
            $this->assertStringContainsString(
                "Processing of message #null is stopped because of an exception:\nTest exception.",
                $messages[1]['message']
            );
        }
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
            new MiddlewareDispatcher($this->createMock(MiddlewareFactoryInterface::class)),
            new MiddlewareDispatcher($this->createMock(MiddlewareFactoryInterface::class)),
        );
    }
}
