<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Implementation;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Push\Implementation\IdMiddleware;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushRequest;

final class IdMiddlewareTest extends TestCase
{
    public function testWithId(): void
    {
        $message = (new GenericMessage('test', null))->withMeta([IdEnvelope::META_ID => 'test-id']);
        $handler = $this->createMock(PushHandlerInterface::class);

        $handler->expects($this->once())
            ->method('handlePush')
            ->willReturnArgument(0);

        $middleware = new IdMiddleware();
        $result = $middleware->processPush(new PushRequest($message, 'test-queue'), $handler);

        $this->assertSame($message, $result->getMessage());
        $this->assertSame('test-queue', $result->getQueueName());
        $this->assertNotInstanceOf(IdEnvelope::class, $result->getMessage());
        $this->assertEquals('test-id', $result->getMessage()->getMeta()[IdEnvelope::META_ID]);
        $this->assertSame($message->getPayload(), $result->getMessage()->getPayload());
        $this->assertSame($message->getType(), $result->getMessage()->getType());
    }

    public function testWithoutId(): void
    {
        $message = new GenericMessage('test', null);
        $handler = $this->createMock(PushHandlerInterface::class);

        $handler->expects($this->once())
            ->method('handlePush')
            ->willReturnArgument(0);

        $middleware = new IdMiddleware();
        $result = $middleware->processPush(new PushRequest($message, 'test-queue'), $handler);

        $this->assertInstanceOf(IdEnvelope::class, $result->getMessage());
        $this->assertNotSame($message, $result->getMessage());
        $this->assertNotEmpty($result->getMessage()->getMeta()[IdEnvelope::META_ID] ?? null);
        $this->assertSame($message->getPayload(), $result->getMessage()->getPayload());
        $this->assertSame($message->getType(), $result->getMessage()->getType());
    }

    public function testWithEmptyId(): void
    {
        $message = (new GenericMessage('test', null))->withMeta([IdEnvelope::META_ID => '']);
        $handler = $this->createMock(PushHandlerInterface::class);

        $handler->expects($this->once())
            ->method('handlePush')
            ->willReturnArgument(0);

        $middleware = new IdMiddleware();
        $result = $middleware->processPush(new PushRequest($message, 'test-queue'), $handler);

        $this->assertInstanceOf(IdEnvelope::class, $result->getMessage());
        $this->assertNotSame($message, $result->getMessage());
        $this->assertNotEmpty($result->getMessage()->getMeta()[IdEnvelope::META_ID] ?? null);
        $this->assertNotSame('', $result->getMessage()->getMeta()[IdEnvelope::META_ID]);
        $this->assertSame($message->getPayload(), $result->getMessage()->getPayload());
        $this->assertSame($message->getType(), $result->getMessage()->getType());
    }
}
