<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactoryInterface;
use Yiisoft\Queue\SyncQueueProducer;
use Yiisoft\Queue\Worker\WorkerInterface;

final class SyncQueueProducerTest extends TestCase
{
    public function testPushLogsPreparationAndCompletion(): void
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
                'Processed message with message type "{messageType}" synchronously.',
                ['messageType' => 'test'],
            );

        $message = new GenericMessage('test', []);
        $worker = $this->createMock(WorkerInterface::class);
        $worker->expects(self::once())
            ->method('process')
            ->with($message, 'queue');

        $producer = new SyncQueueProducer(
            $logger,
            new PushMiddlewareConfig($this->createMock(PushMiddlewareFactoryInterface::class), []),
            $worker,
            'queue',
        );

        self::assertSame($message, $producer->push($message));
    }
}
