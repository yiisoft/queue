<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;

final class RequestImmutabilityTest extends TestCase
{
    public function testPushRequestWithersDoNotMutateOriginal(): void
    {
        $message = new GenericMessage('original', 'payload');
        $request = new PushRequest($message, 'original-queue');

        $withMessage = $request->withMessage(new GenericMessage('changed', 'new'));
        $withQueue = $request->withQueueName('changed-queue');

        self::assertNotSame($request, $withMessage);
        self::assertNotSame($request, $withQueue);
        self::assertSame($message, $request->getMessage());
        self::assertSame('original-queue', $request->getQueueName());
        self::assertSame('changed', $withMessage->getMessage()->getType());
        self::assertSame('original-queue', $withMessage->getQueueName());
        self::assertSame('changed-queue', $withQueue->getQueueName());
        self::assertSame($message, $withQueue->getMessage());
    }

    public function testWorkerRequestWithersDoNotMutateOriginal(): void
    {
        $message = new GenericMessage('original', 'payload');
        $request = new WorkerRequest($message, 'original-queue');

        $withMessage = $request->withMessage(new GenericMessage('changed', 'new'));
        $withQueue = $request->withQueueName('changed-queue');

        self::assertNotSame($request, $withMessage);
        self::assertNotSame($request, $withQueue);
        self::assertSame($message, $request->getMessage());
        self::assertSame('original-queue', $request->getQueueName());
        self::assertSame('changed', $withMessage->getMessage()->getType());
        self::assertSame('original-queue', $withMessage->getQueueName());
        self::assertSame('changed-queue', $withQueue->getQueueName());
        self::assertSame($message, $withQueue->getMessage());
    }
}
