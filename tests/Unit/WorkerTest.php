<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Middleware\MiddlewareFactoryInterface;
use Yiisoft\Queue\Message\HandlerEnvelope;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\Log\SimpleLogger;
use Yiisoft\Queue\Exception\JobFailureException;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\App\FakeHandler;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Queue\Tests\Support\ExceptionMessageHandler;
use Yiisoft\Queue\Tests\Support\StackMessageHandler;

final class WorkerTest extends TestCase
{
    public function testJobExecutedWithDefinitionClassHandler(): void
    {
        $message = new HandlerEnvelope(
            new Message(FakeHandler::class, ['test-data']),
            FakeHandler::class,
        );

        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams(new SimpleLogger(), $container);

        $worker->process($message, $queue);

        $this->assertSame([$message], $handler::$processedMessages);
    }

    public function testHandlerIsReplacedWithEnvelopsOne(): void
    {
        $message = new HandlerEnvelope(
            new Message(['test-data']),
            StackMessageHandler::class,
        );

        $stackMessageHandler = new StackMessageHandler();
        $container = new SimpleContainer([
            StackMessageHandler::class => $stackMessageHandler,
        ]);

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams(new SimpleLogger(), $container);

        $worker->process($message, $queue);
        $this->assertSame([$message], $stackMessageHandler->processedMessages);
    }

    public function testJobFailWithDefinitionHandlerException(): void
    {
        $message = new HandlerEnvelope(
            new Message(['test-data']),
            ExceptionMessageHandler::class,
        );
        $logger = new SimpleLogger();
        $container = new SimpleContainer([ExceptionMessageHandler::class => new ExceptionMessageHandler()]);

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($logger, $container);

        try {
            $worker->process($message, $queue);
        } catch (JobFailureException $exception) {
            self::assertSame($exception::class, JobFailureException::class);
            self::assertSame($exception->getMessage(), "Processing of message #null is stopped because of an exception:\nTest exception.");
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
        ContainerInterface $container
    ): Worker {
        return new Worker(
            $logger,
            new Injector($container),
            $container,
            new MiddlewareDispatcher($this->createMock(MiddlewareFactoryInterface::class)),
            new MiddlewareDispatcher($this->createMock(MiddlewareFactoryInterface::class)),
        );
    }
}
