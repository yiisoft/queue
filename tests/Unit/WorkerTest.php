<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\Test\TestLogger;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Queue\Exception\JobFailureException;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\App\FakeHandler;
use Yiisoft\Yii\Queue\Tests\TestCase;
use Yiisoft\Yii\Queue\Worker\Worker;

final class WorkerTest extends TestCase
{
    public function testJobExecutedWithCallableHandler(): void
    {
        $handleMessage = null;
        $message = new Message('simple', ['test-data']);
        $logger = new TestLogger();
        $container = new SimpleContainer();
        $handlers = [
            'simple' => function (MessageInterface $message) use (&$handleMessage) {
                $handleMessage = $message;
            },
        ];

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $logger, $container);

        $worker->process($message, $queue);
        $this->assertSame($message, $handleMessage);

        $this->assertTrue(
            $logger->hasInfoThatContains('Processing message #{message}.')
        );
    }

    public function testJobExecutedWithDefinitionHandler(): void
    {
        $message = new Message('simple', ['test-data']);
        $logger = new TestLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);
        $handlers = ['simple' => FakeHandler::class];

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $logger, $container);

        $worker->process($message, $queue);
        $this->assertSame([$message], $handler::$processedMessages);
    }

    public function testJobExecutedWithDefinitionClassHandler(): void
    {
        $message = new Message('simple', ['test-data']);
        $logger = new TestLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);
        $handlers = ['simple' => [FakeHandler::class, 'execute']];

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $logger, $container);

        $worker->process($message, $queue);
        $this->assertSame([$message], $handler::$processedMessages);
    }

    public function testJobFailWithDefinitionNotFoundClassButExistInContainerHandler(): void
    {
        $message = new Message('simple', ['test-data']);
        $logger = new TestLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer(['not-found-class-name' => $handler]);
        $handlers = ['simple' => ['not-found-class-name', 'execute']];

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $logger, $container);

        $worker->process($message, $queue);
        $this->assertSame([$message], $handler::$processedMessages);
    }

    public function testJobExecutedWithStaticDefinitionHandler(): void
    {
        $message = new Message('simple', ['test-data']);
        $logger = new TestLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);
        $handlers = ['simple' => [FakeHandler::class, 'staticExecute']];

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $logger, $container);

        $worker->process($message, $queue);
        $this->assertSame([$message], $handler::$processedMessages);
    }

    public function testJobFailWithDefinitionUndefinedMethodHandler(): void
    {
        $this->expectExceptionMessage("Queue handler with name simple doesn't exist");

        $message = new Message('simple', ['test-data']);
        $logger = new TestLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);
        $handlers = ['simple' => [FakeHandler::class, 'undefinedMethod']];

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $logger, $container);

        $worker->process($message, $queue);
    }

    public function testJobFailWithDefinitionUndefinedClassHandler(): void
    {
        $this->expectExceptionMessage("Queue handler with name simple doesn't exist");

        $message = new Message('simple', ['test-data']);
        $logger = new TestLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);
        $handlers = ['simple' => ['UndefinedClass', 'handle']];

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $logger, $container);

        try {
            $worker->process($message, $queue);
        } finally {
            $this->assertTrue(
                $logger->hasErrorThatContains("UndefinedClass doesn't exist.")
            );
        }
    }

    public function testJobFailWithDefinitionClassNotFoundInContainerHandler(): void
    {
        $this->expectExceptionMessage("Queue handler with name simple doesn't exist");
        $message = new Message('simple', ['test-data']);
        $logger = new TestLogger();
        $container = new SimpleContainer();
        $handlers = ['simple' => [FakeHandler::class, 'execute']];

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $logger, $container);

        $worker->process($message, $queue);
    }

    public function testJobFailWithDefinitionHandlerException(): void
    {
        $this->expectException(JobFailureException::class);
        $this->expectExceptionMessage(
            "Processing of message #null is stopped because of an exception:\nTest exception."
        );

        $message = new Message('simple', ['test-data']);
        $logger = new TestLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);
        $handlers = ['simple' => [FakeHandler::class, 'executeWithException']];

        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $logger, $container);

        try {
            $worker->process($message, $queue);
        } finally {
            $this->assertTrue(
                $logger->hasErrorThatContains(
                    "Processing of message #null is stopped because of an exception:\nTest exception."
                )
            );
        }
    }

    private function createWorkerByParams(
        array $handlers,
        LoggerInterface $logger,
        ContainerInterface $container
    ): Worker {
        return new Worker(
            $handlers,
            $logger,
            new Injector($container),
            $container
        );
    }
}
