<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\Log\SimpleLogger;
use Yiisoft\Queue\Exception\JobFailureException;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\MiddlewareFactoryConsumeInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFactoryFailureInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\App\FakeHandler;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Worker\Worker;

final class WorkerTest extends TestCase
{
    public function testJobExecutedWithCallableHandler(): void
    {
        $handleMessage = null;
        $message = new Message('simple', ['test-data']);
        $logger = new SimpleLogger();
        $container = new SimpleContainer();

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($logger, $container);

        $worker->process($message, $queue);
        $this->assertSame($message, $handleMessage);

        $messages = $logger->getMessages();
        $this->assertNotEmpty($messages);
        $this->assertStringContainsString('Processing message #null.', $messages[0]['message']);
    }

    public function testJobExecutedWithDefinitionHandler(): void
    {
        $message = new Message('simple', ['test-data']);
        $logger = new SimpleLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($logger, $container);

        $worker->process($message, $queue);
        $this->assertSame([$message], $handler::$processedMessages);
    }

    public function testJobExecutedWithDefinitionClassHandler(): void
    {
        $message = new Message('simple', ['test-data']);
        $logger = new SimpleLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($logger, $container);

        $worker->process($message, $queue);
        $this->assertSame([$message], $handler::$processedMessages);
    }

    public function testJobFailWithDefinitionNotFoundClassButExistInContainerHandler(): void
    {
        $message = new Message('simple', ['test-data']);
        $logger = new SimpleLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer(['not-found-class-name' => $handler]);

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams( $logger, $container);

        $worker->process($message, $queue);
        $this->assertSame([$message], $handler::$processedMessages);
    }

    public function testJobExecutedWithStaticDefinitionHandler(): void
    {
        $message = new Message('simple', ['test-data']);
        $logger = new SimpleLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams( $logger, $container);

        $worker->process($message, $queue);
        $this->assertSame([$message], $handler::$processedMessages);
    }

    public function testJobFailWithDefinitionUndefinedMethodHandler(): void
    {
        $this->expectExceptionMessage("Queue handler with name simple doesn't exist");

        $message = new Message('simple', ['test-data']);
        $logger = new SimpleLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams( $logger, $container);

        $worker->process($message, $queue);
    }

    public function testJobFailWithDefinitionUndefinedClassHandler(): void
    {
        $this->expectExceptionMessage("Queue handler with name simple doesn't exist");

        $message = new Message('simple', ['test-data']);
        $logger = new SimpleLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams( $logger, $container);

        try {
            $worker->process($message, $queue);
        } finally {
            $messages = $logger->getMessages();
            $this->assertNotEmpty($messages);
            $this->assertStringContainsString('UndefinedClass doesn\'t exist.', $messages[1]['message']);
        }
    }

    public function testJobFailWithDefinitionClassNotFoundInContainerHandler(): void
    {
        $this->expectExceptionMessage("Queue handler with name simple doesn't exist");
        $message = new Message('simple', ['test-data']);
        $logger = new SimpleLogger();
        $container = new SimpleContainer();

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams( $logger, $container);

        $worker->process($message, $queue);
    }

    public function testJobFailWithDefinitionHandlerException(): void
    {
        $message = new Message('simple', ['test-data']);
        $logger = new SimpleLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);

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
            new ConsumeMiddlewareDispatcher($this->createMock(MiddlewareFactoryConsumeInterface::class)),
            new FailureMiddlewareDispatcher($this->createMock(MiddlewareFactoryFailureInterface::class), []),
        );
    }
}
