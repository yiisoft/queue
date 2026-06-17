<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Implementation;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Push\Implementation\IdMiddleware;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;

final class IdMiddlewareTest extends TestCase
{
    public function testWithId(): void
    {
        $message = (new GenericMessage('test', null))->withMetadata([IdEnvelope::META_ID => 'test-id']);
        $handler = $this->createMock(PushHandlerInterface::class);

        $handler->expects($this->once())
            ->method('handlePush')
            ->willReturnArgument(0);

        $middleware = new IdMiddleware();
        $result = $middleware->processPush($message, $handler);

        $this->assertSame($message, $result);
        $this->assertNotInstanceOf(IdEnvelope::class, $result);
        $this->assertEquals('test-id', $result->getMetadata()[IdEnvelope::META_ID]);
        $this->assertSame($message->getPayload(), $result->getPayload());
        $this->assertSame($message->getType(), $result->getType());
    }

    public function testWithoutId(): void
    {
        $message = new GenericMessage('test', null);
        $handler = $this->createMock(PushHandlerInterface::class);

        $handler->expects($this->once())
            ->method('handlePush')
            ->willReturnArgument(0);

        $middleware = new IdMiddleware();
        $result = $middleware->processPush($message, $handler);

        $this->assertInstanceOf(IdEnvelope::class, $result);
        $this->assertNotSame($message, $result);
        $this->assertNotEmpty($result->getMetadata()[IdEnvelope::META_ID] ?? null);
        $this->assertSame($message->getPayload(), $result->getPayload());
        $this->assertSame($message->getType(), $result->getType());
    }

    public function testWithEmptyId(): void
    {
        $message = (new GenericMessage('test', null))->withMetadata([IdEnvelope::META_ID => '']);
        $handler = $this->createMock(PushHandlerInterface::class);

        $handler->expects($this->once())
            ->method('handlePush')
            ->willReturnArgument(0);

        $middleware = new IdMiddleware();
        $result = $middleware->processPush($message, $handler);

        $this->assertInstanceOf(IdEnvelope::class, $result);
        $this->assertNotSame($message, $result);
        $this->assertNotEmpty($result->getMetadata()[IdEnvelope::META_ID] ?? null);
        $this->assertNotSame('', $result->getMetadata()[IdEnvelope::META_ID]);
        $this->assertSame($message->getPayload(), $result->getPayload());
        $this->assertSame($message->getType(), $result->getType());
    }
}
