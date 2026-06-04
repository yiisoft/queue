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
use Yiisoft\Queue\Exception\MessageFailureException;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\App\FakeHandler;
use Yiisoft\Queue\Tests\App\StaticMessageHandler;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Worker\Worker;
use PHPUnit\Framework\MockObject\MockObject;

final class WorkerTest extends TestCase
{
    #[DataProvider('messageHandledDataProvider')]
    public function testMessageHandled(mixed $handler, array $containerServices): void
    {
        $message = new GenericMessage('simple', ['test-data']);
        $logger = new SimpleLogger();
        $container = new SimpleContainer($containerServices);
        $handlers = ['simple' => $handler];

        /** @var MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container, $logger);

        $worker->process($message, $queue);

        $processedMessages = FakeHandler::$processedMessages;
        FakeHandler::$processedMessages = [];

        $this->assertSame([$message], $processedMessages);

        $messages = $logger->getMessages();
        $this->assertNotEmpty($messages);
        $this->assertStringContainsString('Processing message without ID.', $messages[0]['message']);
    }

    public static function messageHandledDataProvider(): iterable
    {
        yield 'definition' => [
            FakeHandler::class,
            [FakeHandler::class => new FakeHandler()],
        ];
        yield 'definition-object' => [
            [new FakeHandler(), 'handle'],
            [],
        ];
        yield 'definition-class' => [
            [FakeHandler::class, 'handle'],
            [FakeHandler::class => new FakeHandler()],
        ];
        yield 'definition-not-found-class-but-exist-in-container' => [
            ['not-found-class-name', 'handle'],
            ['not-found-class-name' => new FakeHandler()],
        ];
        yield 'static-definition' => [
            FakeHandler::staticHandle(...),
            [FakeHandler::class => new FakeHandler()],
        ];
        yield 'callable' => [
            function (MessageInterface $message) {
                FakeHandler::$processedMessages[] = $message;
            },
            [],
        ];
    }

    public function testMessageFailWithDefinitionUndefinedMethodHandler(): void
    {
        $this->expectExceptionMessage('Queue handler for message type "simple" does not exist');

        $message = new GenericMessage('simple', ['test-data']);
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);
        $handlers = ['simple' => [FakeHandler::class, 'undefinedMethod']];

        /** @var MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container);

        $worker->process($message, $queue);
    }

    public function testMessageFailWithDefinitionUndefinedClassHandler(): void
    {
        $this->expectExceptionMessage('Queue handler for message type "simple" does not exist');

        $message = new GenericMessage('simple', ['test-data']);
        $logger = new SimpleLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);
        $handlers = ['simple' => ['UndefinedClass', 'handle']];

        /** @var MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container, $logger);

        $worker->process($message, $queue);
    }

    public function testMessageFailWithDefinitionClassNotFoundInContainerHandler(): void
    {
        $this->expectExceptionMessage('Queue handler for message type "simple" does not exist');
        $message = new GenericMessage('simple', ['test-data']);
        $container = new SimpleContainer();
        $handlers = ['simple' => [FakeHandler::class, 'handle']];

        /** @var MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container);

        $worker->process($message, $queue);
    }

    public function testMessageFailWithDefinitionHandlerException(): void
    {
        $message = new GenericMessage('simple', ['test-data']);
        $logger = new SimpleLogger();
        $handler = new FakeHandler();
        $container = new SimpleContainer([FakeHandler::class => $handler]);
        $handlers = ['simple' => [FakeHandler::class, 'handleWithException']];

        /** @var MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container, $logger);

        try {
            $worker->process($message, $queue);
        } catch (MessageFailureException $exception) {
            self::assertSame($exception::class, MessageFailureException::class);
            self::assertSame($exception->getMessage(), "Processing of message without ID is stopped because of an exception:\nTest exception.");
            self::assertEquals(['test-data'], $exception->getQueueMessage()->getData());
        } finally {
            $messages = $logger->getMessages();
            $this->assertNotEmpty($messages);
            $this->assertStringContainsString(
                "Processing of message without ID is stopped because of an exception:\nTest exception.",
                $messages[1]['message'],
            );
        }
    }

    public function testHandlerNotFoundInContainer(): void
    {
        $message = new GenericMessage('nonexistent', ['test-data']);
        $container = new SimpleContainer();
        $handlers = [];

        /** @var MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Queue handler for message type "nonexistent" does not exist');
        $worker->process($message, $queue);
    }

    public function testHandlerInContainerNotImplementingInterface(): void
    {
        $message = new GenericMessage('invalid', ['test-data']);
        $container = new SimpleContainer([
            'invalid' => new class {
                public function handle(): void {}
            },
        ]);
        $handlers = [];

        /** @var MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Queue handler for message type "invalid" does not exist');
        $worker->process($message, $queue);
    }

    public function testMessageFailureIsHandledSuccessfully(): void
    {
        $message = new GenericMessage('simple', null);
        /** @var MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $queue->method('getName')->willReturn('test-queue');

        $originalException = new RuntimeException('Consume failed');
        /** @var ConsumeMiddlewareInterface&MockObject $consumeMiddleware */
        $consumeMiddleware = $this->createMock(ConsumeMiddlewareInterface::class);
        $consumeMiddleware->method('processConsume')->willThrowException($originalException);

        /** @var ConsumeMiddlewareFactoryInterface&MockObject $consumeMiddlewareFactory */
        $consumeMiddlewareFactory = $this->createMock(ConsumeMiddlewareFactoryInterface::class);
        $consumeMiddlewareFactory->method('createConsumeMiddleware')->willReturn($consumeMiddleware);
        $consumeDispatcher = new ConsumeMiddlewareDispatcher($consumeMiddlewareFactory, 'simple');

        $finalMessage = new GenericMessage('final', null);
        /** @var FailureMiddlewareInterface&MockObject $failureMiddleware */
        $failureMiddleware = $this->createMock(FailureMiddlewareInterface::class);
        $failureMiddleware->method('processFailure')->willReturn(new FailureHandlingRequest($finalMessage, $originalException, $queue));

        /** @var FailureMiddlewareFactoryInterface&MockObject $failureMiddlewareFactory */
        $failureMiddlewareFactory = $this->createMock(FailureMiddlewareFactoryInterface::class);
        $failureMiddlewareFactory->method('createFailureMiddleware')->willReturn($failureMiddleware);
        $failureDispatcher = new FailureMiddlewareDispatcher($failureMiddlewareFactory, ['test-queue' => ['simple']]);

        $container = new SimpleContainer();
        $worker = new Worker(
            ['simple' => fn() => null],
            new NullLogger(),
            new Injector($container),
            $container,
            $consumeDispatcher,
            $failureDispatcher,
            new CallableFactory($container),
        );

        $result = $worker->process($message, $queue);

        self::assertSame($finalMessage, $result);
    }

    public function testStaticMethodHandler(): void
    {
        $message = new GenericMessage('static-handler', ['test-data']);
        $container = new SimpleContainer();
        $handlers = [
            'static-handler' => StaticMessageHandler::handle(...),
        ];

        /** @var MockObject&QueueInterface $queue */
        $queue = $this->createMock(QueueInterface::class);
        $worker = $this->createWorkerByParams($handlers, $container);

        StaticMessageHandler::$wasHandled = false;
        $worker->process($message, $queue);
        $this->assertTrue(StaticMessageHandler::$wasHandled);
    }

    private function createWorkerByParams(
        array $handlers,
        ContainerInterface $container,
        ?LoggerInterface $logger = null,
    ): Worker {
        /** @var ConsumeMiddlewareFactoryInterface&MockObject $consumeMiddlewareFactory */
        $consumeMiddlewareFactory = $this->createMock(ConsumeMiddlewareFactoryInterface::class);
        /** @var FailureMiddlewareFactoryInterface&MockObject $failureMiddlewareFactory */
        $failureMiddlewareFactory = $this->createMock(FailureMiddlewareFactoryInterface::class);

        return new Worker(
            $handlers,
            $logger ?? new NullLogger(),
            new Injector($container),
            $container,
            new ConsumeMiddlewareDispatcher($consumeMiddlewareFactory),
            new FailureMiddlewareDispatcher($failureMiddlewareFactory, []),
            new CallableFactory($container),
        );
    }
}
