<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use RuntimeException;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\Log\SimpleLogger;
use Yiisoft\Queue\Exception\MessageFailureException;
use Yiisoft\Queue\Message\Handler\HandlerNotFoundException;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Message\Envelope;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeHandlerInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareInterface;
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
            self::assertSame("Processing of message without ID is stopped because of an exception:\nTest exception", $exception->getMessage());
            self::assertEquals(['test-data'], $exception->getQueueMessage()->getPayload());
        } finally {
            $messages = $logger->getMessages();
            $this->assertNotEmpty($messages);
            $this->assertStringContainsString(
                "Processing of message without ID is stopped because of an exception:\nTest exception",
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
        $failureMiddleware
            ->expects(self::once())
            ->method('processFailure')
            ->with(self::callback(
                static fn(FailureHandlingRequest $request): bool => $request->getException() === $originalException,
            ))
            ->willReturn(new FailureHandlingRequest($finalMessage, $originalException, $queueName));

        /** @var FailureMiddlewareFactoryInterface&MockObject $failureMiddlewareFactory */
        $failureMiddlewareFactory = $this->createMock(FailureMiddlewareFactoryInterface::class);
        $failureMiddlewareFactory->method('createFailureMiddleware')->willReturn($failureMiddleware);
        $failureDispatcher = new FailureMiddlewareDispatcher($failureMiddlewareFactory, ['test-queue' => ['simple']]);

        $handlerResolver = $this->createHandlerResolver($message, static fn() => null);
        $worker = $this->createWorkerByParams($handlerResolver, new NullLogger(), $consumeDispatcher, $failureDispatcher);

        $worker->process($message, $queueName);
    }

    public function testUnresolvableHandlerIsHandledByFailurePipeline(): void
    {
        $message = new GenericMessage('unsupported', null);
        $queueName = 'test-queue';
        $handlerResolver = new HandlerResolver([], new SimpleContainer());

        $finalMessage = new GenericMessage('final', null);
        /** @var FailureMiddlewareInterface&MockObject $failureMiddleware */
        $failureMiddleware = $this->createMock(FailureMiddlewareInterface::class);
        $failureMiddleware
            ->expects(self::once())
            ->method('processFailure')
            ->with(self::callback(
                static fn(FailureHandlingRequest $request): bool => $request->getException() instanceof HandlerNotFoundException,
            ))
            ->willReturn(new FailureHandlingRequest($finalMessage, new RuntimeException('unused'), $queueName));

        /** @var FailureMiddlewareFactoryInterface&MockObject $failureMiddlewareFactory */
        $failureMiddlewareFactory = $this->createMock(FailureMiddlewareFactoryInterface::class);
        $failureMiddlewareFactory->method('createFailureMiddleware')->willReturn($failureMiddleware);
        $failureDispatcher = new FailureMiddlewareDispatcher($failureMiddlewareFactory, [$queueName => ['simple']]);

        $worker = $this->createWorkerByParams($handlerResolver, failureMiddlewareDispatcher: $failureDispatcher);

        $worker->process($message, $queueName);
    }

    public function testHandlerReceivesTheMessageAndNotTheEnvelopeAroundIt(): void
    {
        $message = new GenericMessage('simple', ['test-data']);
        $envelope = new IdEnvelope($message, 'id-42');

        $received = null;
        $handlerResolver = $this->createHandlerResolver(
            $message,
            static function (MessageInterface $message) use (&$received): void {
                $received = $message;
            },
        );

        $this->createWorkerByParams($handlerResolver)->process($envelope, 'test-queue');

        // The handler is registered for the message type and gets that message, whatever the
        // adapter wrapped it in on the way back.
        $this->assertInstanceOf(GenericMessage::class, $received);
        $this->assertNotInstanceOf(Envelope::class, $received);
        $this->assertSame($message->getType(), $received->getType());
        $this->assertSame($message->getPayload(), $received->getPayload());

        // What the envelope carried comes with it, so a handler that logs or de-duplicates on
        // the id still has one.
        $this->assertSame($envelope->getMeta(), $received->getMeta());
        $this->assertSame('id-42', IdEnvelope::fromMessage($received)->getId());
    }

    public function testConsumeMiddlewareStillSeesTheEnvelope(): void
    {
        $message = new GenericMessage('simple', null);
        $envelope = new IdEnvelope($message, 'id-42');

        $seenByMiddleware = null;
        /** @var ConsumeMiddlewareInterface&MockObject $consumeMiddleware */
        $consumeMiddleware = $this->createMock(ConsumeMiddlewareInterface::class);
        $consumeMiddleware
            ->method('processConsume')
            ->willReturnCallback(
                static function (ConsumeRequest $request, ConsumeHandlerInterface $handler) use (&$seenByMiddleware) {
                    $seenByMiddleware = $request->getMessage();

                    return $handler->handleConsume($request);
                },
            );

        /** @var ConsumeMiddlewareFactoryInterface&MockObject $consumeMiddlewareFactory */
        $consumeMiddlewareFactory = $this->createMock(ConsumeMiddlewareFactoryInterface::class);
        $consumeMiddlewareFactory->method('createConsumeMiddleware')->willReturn($consumeMiddleware);
        $consumeDispatcher = new ConsumeMiddlewareDispatcher($consumeMiddlewareFactory, 'simple');

        $receivedByHandler = null;
        $handlerResolver = $this->createHandlerResolver(
            $message,
            static function (MessageInterface $message) use (&$receivedByHandler): void {
                $receivedByHandler = $message;
            },
        );

        $worker = $this->createWorkerByParams($handlerResolver, new NullLogger(), $consumeDispatcher);
        $worker->process($envelope, 'test-queue');

        // Unwrapping happens at the last step, so middleware keep the envelope itself, and only
        // the handler is given the message — with the meta the envelope carried.
        $this->assertSame($envelope, $seenByMiddleware);
        $this->assertNotInstanceOf(Envelope::class, $receivedByHandler);
        $this->assertSame('id-42', IdEnvelope::fromMessage($receivedByHandler)->getId());
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
    ): Worker {
        /** @var ConsumeMiddlewareFactoryInterface&MockObject $consumeMiddlewareFactory */
        $consumeMiddlewareFactory = $this->createMock(ConsumeMiddlewareFactoryInterface::class);
        /** @var FailureMiddlewareFactoryInterface&MockObject $failureMiddlewareFactory */
        $failureMiddlewareFactory = $this->createMock(FailureMiddlewareFactoryInterface::class);

        return new Worker(
            $logger ?? new NullLogger(),
            $consumeMiddlewareDispatcher ?? new ConsumeMiddlewareDispatcher($consumeMiddlewareFactory),
            $failureMiddlewareDispatcher ?? new FailureMiddlewareDispatcher($failureMiddlewareFactory, []),
            $handlerResolver,
        );
    }
}
