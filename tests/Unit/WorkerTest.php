<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Throwable;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\Log\SimpleLogger;
use Yiisoft\Queue\Exception\MessageFailureException;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareInterface;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Worker\WorkerHandlerInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareFactory;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;
use Yiisoft\Queue\Tests\App\FakeHandler;
use Yiisoft\Queue\Tests\TestCase;
use Yiisoft\Queue\Worker\Worker;
use PHPUnit\Framework\MockObject\MockObject;

final class WorkerTest extends TestCase
{
    public function testMessageHandled(): void
    {
        $message = new GenericMessage('simple', ['test-data']);
        $logger = new SimpleLogger();
        $handlerResolver = $this->createHandlerResolver($message, static function (MessageInterface $message): void {
            FakeHandler::$processedMessages[] = $message;
        });

        $worker = $this->createWorkerByParams($handlerResolver, $logger);
        $worker->process($message, 'test-queue');

        $processedMessages = FakeHandler::$processedMessages;
        FakeHandler::$processedMessages = [];

        $this->assertSame([$message], $processedMessages);

        $messages = $logger->getMessages();
        $this->assertNotEmpty($messages);
        $this->assertStringContainsString('Processing message without ID.', $messages[0]['message']);
    }

    public function testMessageFailWithDefinitionHandlerException(): void
    {
        $message = new GenericMessage('simple', ['test-data']);
        $logger = new SimpleLogger();
        $handlerResolver = $this->createHandlerResolver($message, static function (): never {
            throw new RuntimeException('Test exception');
        });

        $worker = $this->createWorkerByParams($handlerResolver, $logger);

        try {
            $worker->process($message, 'test-queue');
            self::fail('Exception was not thrown.');
        } catch (MessageFailureException $exception) {
            self::assertSame($exception::class, MessageFailureException::class);
            self::assertSame($exception->getMessage(), "Processing of message without ID is stopped because of an exception:\nTest exception.");
            self::assertEquals(['test-data'], $exception->getQueueMessage()->getPayload());
        } finally {
            $messages = $logger->getMessages();
            $this->assertNotEmpty($messages);
            $this->assertStringContainsString(
                "Processing of message without ID is stopped because of an exception:\nTest exception.",
                $messages[1]['message'],
            );
        }
    }

    public function testMessageFailureIsHandledSuccessfully(): void
    {
        $message = new GenericMessage('simple', null);
        $queueName = 'test-queue';

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
        $failureMiddleware->method('processFailure')->willReturn(new FailureHandlingRequest($finalMessage, $originalException, $queueName));

        /** @var FailureMiddlewareFactoryInterface&MockObject $failureMiddlewareFactory */
        $failureMiddlewareFactory = $this->createMock(FailureMiddlewareFactoryInterface::class);
        $failureMiddlewareFactory->method('createFailureMiddleware')->willReturn($failureMiddleware);
        $failureDispatcher = new FailureMiddlewareDispatcher($failureMiddlewareFactory, ['test-queue' => ['simple']]);

        $handlerResolver = $this->createHandlerResolver($message, static fn() => null);
        $worker = $this->createWorkerByParams($handlerResolver, new NullLogger(), $consumeDispatcher, $failureDispatcher);

        $result = $worker->process($message, $queueName);

        self::assertSame($finalMessage, $result);
    }

    public function testWorkerMiddlewareRunsBeforeHandlerResolution(): void
    {
        $message = new GenericMessage('missing', null);
        $seen = false;
        $workerMiddleware = new WorkerMiddlewareDispatcher(
            new WorkerMiddlewareFactory(new SimpleContainer(), new CallableFactory(new SimpleContainer())),
            [static function (WorkerRequest $request, WorkerHandlerInterface $handler) use (&$seen): WorkerRequest {
                $seen = true;
                return $handler->handleWorker($request);
            }],
        );
        $worker = $this->createWorkerByParams(
            new HandlerResolver([], new SimpleContainer()),
            new NullLogger(),
            null,
            null,
            $workerMiddleware,
        );

        $this->expectException(Throwable::class);
        try {
            $worker->process($message, 'queue');
        } finally {
            self::assertTrue($seen);
        }
    }

    private function createHandlerResolver(MessageInterface $message, callable $handler): HandlerResolver
    {
        $container = new SimpleContainer();

        return new HandlerResolver(
            [$message->getType() => $handler],
            $container,
        );
    }

    private function createWorkerByParams(
        HandlerResolver $handlerResolver,
        ?LoggerInterface $logger = null,
        ?ConsumeMiddlewareDispatcher $consumeMiddlewareDispatcher = null,
        ?FailureMiddlewareDispatcher $failureMiddlewareDispatcher = null,
        ?WorkerMiddlewareDispatcher $workerMiddlewareDispatcher = null,
    ): Worker {
        /** @var ConsumeMiddlewareFactoryInterface&MockObject $consumeMiddlewareFactory */
        $consumeMiddlewareFactory = $this->createMock(ConsumeMiddlewareFactoryInterface::class);
        /** @var FailureMiddlewareFactoryInterface&MockObject $failureMiddlewareFactory */
        $failureMiddlewareFactory = $this->createMock(FailureMiddlewareFactoryInterface::class);

        $workerMiddlewareDispatcher ??= new WorkerMiddlewareDispatcher(
            new WorkerMiddlewareFactory(new SimpleContainer(), new CallableFactory(new SimpleContainer())),
        );

        return new Worker(
            $logger ?? new NullLogger(),
            $consumeMiddlewareDispatcher ?? new ConsumeMiddlewareDispatcher($consumeMiddlewareFactory),
            $failureMiddlewareDispatcher ?? new FailureMiddlewareDispatcher($failureMiddlewareFactory, []),
            $handlerResolver,
            $workerMiddlewareDispatcher,
        );
    }
}
