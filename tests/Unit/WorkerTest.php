<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\Log\SimpleLogger;
use Yiisoft\Queue\Exception\JobFailureException;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\MiddlewareFactoryConsumeInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\MiddlewareConsumeInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFailureInterface;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFactoryFailureInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\App\FakeHandler;
use Yiisoft\Queue\Tests\App\StaticMessageHandler;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Worker\Worker;

final class WorkerTest extends TestCase
{
    #[DataProvider('jobExecutedDataProvider')]
    public function testJobExecuted($handler, array $containerServices): void
    {
        $message = new Message('simple', ['test-data']);
        $logger = new SimpleLogger();
        $container = new SimpleContainer($containerServices);
        $handlers = ['simple' => $handler];

        /** @var \PHPUnit\Framework\MockObject\MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container, $logger);

        $worker->process($message, $queue);

        $processedMessages = FakeHandler::$processedMessages;
        FakeHandler::$processedMessages = [];

        $this->assertSame([$message], $processedMessages);

        $messages = $logger->getMessages();
        $this->assertNotEmpty($messages);
        $this->assertStringContainsString('Processing message #null.', $messages[0]['message']);
    }

    public static function jobExecutedDataProvider(): iterable
    {
        yield 'definition' => [
            FakeHandler::class,
            [FakeHandler::class => new FakeHandler()],
        ];
        yield 'definition-class' => [
            [FakeHandler::class, 'execute'],
            [FakeHandler::class => new FakeHandler()],
        ];
        yield 'definition-not-found-class-but-exist-in-container' => [
            ['not-found-class-name', 'execute'],
            ['not-found-class-name' => new FakeHandler()],
        ];
        yield 'static-definition' => [
            FakeHandler::staticExecute(...),
            [FakeHandler::class => new FakeHandler()],
        ];
        yield 'callable' => [
            function (MessageInterface $message) {
                FakeHandler::$processedMessages[] = $message;
            },
            [],
        ];
    }

    public function testJobFailWithDefinitionUndefinedMethodHandler(): void
    {
        $this->expectExceptionMessage('Queue handler with name "simple" does not exist');

        $message = new Message('simple', ['test-data']);
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);
        $handlers = ['simple' => [FakeHandler::class, 'undefinedMethod']];

        /** @var \PHPUnit\Framework\MockObject\MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container);

        $worker->process($message, $queue);
    }

    public function testJobFailWithDefinitionUndefinedClassHandler(): void
    {
        $this->expectExceptionMessage('Queue handler with name "simple" does not exist');

        $message = new Message('simple', ['test-data']);
        $logger = new SimpleLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);
        $handlers = ['simple' => ['UndefinedClass', 'handle']];

        /** @var \PHPUnit\Framework\MockObject\MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container, $logger);

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
        $this->expectExceptionMessage('Queue handler with name "simple" does not exist');
        $message = new Message('simple', ['test-data']);
        $container = new SimpleContainer();
        $handlers = ['simple' => [FakeHandler::class, 'execute']];

        /** @var \PHPUnit\Framework\MockObject\MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container);

        $worker->process($message, $queue);
    }

    public function testJobFailWithDefinitionHandlerException(): void
    {
        $message = new Message('simple', ['test-data']);
        $logger = new SimpleLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);
        $handlers = ['simple' => [FakeHandler::class, 'executeWithException']];

        /** @var \PHPUnit\Framework\MockObject\MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container, $logger);

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
        array $handlers,
        ContainerInterface $container,
        ?LoggerInterface $logger = null,
    ): Worker {
        /** @var MiddlewareFactoryConsumeInterface&\PHPUnit\Framework\MockObject\MockObject $consumeMiddlewareFactory */
        $consumeMiddlewareFactory = $this->createMock(MiddlewareFactoryConsumeInterface::class);
        /** @var MiddlewareFactoryFailureInterface&\PHPUnit\Framework\MockObject\MockObject $failureMiddlewareFactory */
        $failureMiddlewareFactory = $this->createMock(MiddlewareFactoryFailureInterface::class);

        return new Worker(
            $handlers,
            $logger ?? new NullLogger(),
            new Injector($container),
            $container,
            new ConsumeMiddlewareDispatcher($consumeMiddlewareFactory),
            new FailureMiddlewareDispatcher($failureMiddlewareFactory, []),
        );
    }

    public function testHandlerNotFoundInContainer(): void
    {
        $message = new Message('nonexistent', ['test-data']);
        $container = new SimpleContainer();
        $handlers = [];

        /** @var \PHPUnit\Framework\MockObject\MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Queue handler with name "nonexistent" does not exist');
        $worker->process($message, $queue);
    }

    public function testHandlerInContainerNotImplementingInterface(): void
    {
        $message = new Message('invalid', ['test-data']);
        $container = new SimpleContainer([
            'invalid' => new class () {
                public function handle(): void
                {
                }
            },
        ]);
        $handlers = [];

        /** @var \PHPUnit\Framework\MockObject\MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Queue handler with name "invalid" does not exist');
        $worker->process($message, $queue);
    }

    public function testJobFailureIsHandledSuccessfully(): void
    {
        $message = new Message('simple', null);
        /** @var \PHPUnit\Framework\MockObject\MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $queue->method('getChannel')->willReturn('test-channel');

        $originalException = new RuntimeException('Consume failed');
        /** @var MiddlewareConsumeInterface&\PHPUnit\Framework\MockObject\MockObject $consumeMiddleware */
        $consumeMiddleware = $this->createMock(MiddlewareConsumeInterface::class);
        $consumeMiddleware->method('processConsume')->willThrowException($originalException);

        /** @var MiddlewareFactoryConsumeInterface&\PHPUnit\Framework\MockObject\MockObject $consumeMiddlewareFactory */
        $consumeMiddlewareFactory = $this->createMock(MiddlewareFactoryConsumeInterface::class);
        $consumeMiddlewareFactory->method('createConsumeMiddleware')->willReturn($consumeMiddleware);
        $consumeDispatcher = new ConsumeMiddlewareDispatcher($consumeMiddlewareFactory, 'simple');

        $finalMessage = new Message('final', null);
        /** @var MiddlewareFailureInterface&\PHPUnit\Framework\MockObject\MockObject $failureMiddleware */
        $failureMiddleware = $this->createMock(MiddlewareFailureInterface::class);
        $failureMiddleware->method('processFailure')->willReturn(new FailureHandlingRequest($finalMessage, $originalException, $queue));

        /** @var MiddlewareFactoryFailureInterface&\PHPUnit\Framework\MockObject\MockObject $failureMiddlewareFactory */
        $failureMiddlewareFactory = $this->createMock(MiddlewareFactoryFailureInterface::class);
        $failureMiddlewareFactory->method('createFailureMiddleware')->willReturn($failureMiddleware);
        $failureDispatcher = new FailureMiddlewareDispatcher($failureMiddlewareFactory, ['test-channel' => ['simple']]);

        $worker = new Worker(
            ['simple' => fn () => null],
            new NullLogger(),
            new Injector(new SimpleContainer()),
            new SimpleContainer(),
            $consumeDispatcher,
            $failureDispatcher
        );

        $result = $worker->process($message, $queue);

        self::assertSame($finalMessage, $result);
    }

    public function testStaticMethodHandler(): void
    {
        $message = new Message('static-handler', ['test-data']);
        $container = new SimpleContainer();
        $handlers = [
            'static-handler' => StaticMessageHandler::handle(...),
        ];

        /** @var \PHPUnit\Framework\MockObject\MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container);

        StaticMessageHandler::$wasHandled = false;
        $worker->process($message, $queue);
        $this->assertTrue(StaticMessageHandler::$wasHandled);
    }
}
