<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\AsyncQueueProducer;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareInterface;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Stubs\InMemoryAdapter;

final class AsyncQueueProducerTest extends TestCase
{
    public function testPushLogsPreparationAndCompletionWithAssignedId(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'Preparing to push message with message type "{messageType}".',
                ['messageType' => 'test'],
            );
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'Pushed message with message type "{messageType}" to the queue. Assigned ID #{id}.',
                ['messageType' => 'test', 'id' => 0],
            );

        $producer = new AsyncQueueProducer(
            $logger,
            new PushMiddlewareConfig($this->createMock(PushMiddlewareFactoryInterface::class), []),
            new InMemoryAdapter(),
        );

        $producer->push(new GenericMessage('test', []));
    }

    public function testPushLogsCompletionWithoutAssignedId(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('debug')
            ->with(
                'Preparing to push message with message type "{messageType}".',
                ['messageType' => 'test'],
            );
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'Pushed message with message type "{messageType}" to the queue. ID doesn\'t assigned.',
                ['messageType' => 'test', 'id' => null],
            );

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter->expects(self::once())
            ->method('push')
            ->willReturnArgument(0);

        $producer = new AsyncQueueProducer(
            $logger,
            new PushMiddlewareConfig($this->createMock(PushMiddlewareFactoryInterface::class), []),
            $adapter,
        );

        $producer->push(new GenericMessage('test', []));
    }

    public function testCommonAndQueueSpecificMiddlewareDefinitionsAreCombined(): void
    {
        $definitions = [];
        $factory = $this->createMock(PushMiddlewareFactoryInterface::class);
        $factory->expects(self::exactly(2))
            ->method('createPushMiddleware')
            ->willReturnCallback(function (mixed $definition) use (&$definitions): PushMiddlewareInterface {
                $definitions[] = $definition;

                return new class ($definition) implements PushMiddlewareInterface {
                    public function __construct(private readonly string $stage) {}

                    public function processPush(PushRequest $request, PushHandlerInterface $handler): PushRequest
                    {
                        $message = $request->getMessage();
                        $payload = (array) $message->getPayload();
                        $payload[] = $this->stage;

                        return $handler->handlePush($request->withMessage(
                            new GenericMessage($message->getType(), $payload),
                        ));
                    }
                };
            });

        $adapter = new InMemoryAdapter();
        $producer = new AsyncQueueProducer(
            new NullLogger(),
            new PushMiddlewareConfig($factory, ['common']),
            $adapter,
            'queue',
            ['specific'],
        );

        $producer->push(new GenericMessage('test', []));

        self::assertSame(['common', 'specific'], $definitions);
        self::assertSame(['common', 'specific'], $adapter->getMessagesList()[0]->getPayload());
    }
}
