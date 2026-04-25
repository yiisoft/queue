<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Implementation;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Push\Implementation\IdMiddleware;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;

final class IdMiddlewareTest extends TestCase
{
    public function testWithId(): void
    {
        $message = new Message('test', null, [IdEnvelope::MESSAGE_ID_KEY => 'test-id']);
        $handler = $this->createMock(MessageHandlerPushInterface::class);

        $handler->expects($this->once())
            ->method('handlePush')
            ->willReturnArgument(0);

        $middleware = new IdMiddleware();
        $result = $middleware->processPush($message, $handler);

        $this->assertSame($message, $result);
        $this->assertNotInstanceOf(IdEnvelope::class, $result);
        $this->assertEquals('test-id', $result->getMetadata()[IdEnvelope::MESSAGE_ID_KEY]);
        $this->assertSame($message->getData(), $result->getData());
        $this->assertSame($message->getType(), $result->getType());
    }

    public function testWithoutId(): void
    {
        $message = new Message('test', null);
        $handler = $this->createMock(MessageHandlerPushInterface::class);

        $handler->expects($this->once())
            ->method('handlePush')
            ->willReturnArgument(0);

        $middleware = new IdMiddleware();
        $result = $middleware->processPush($message, $handler);

        $this->assertInstanceOf(IdEnvelope::class, $result);
        $this->assertNotSame($message, $result);
        $this->assertNotEmpty($result->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? null);
        $this->assertSame($message->getData(), $result->getData());
        $this->assertSame($message->getType(), $result->getType());
    }
}
