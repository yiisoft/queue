<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Implementation;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Push\Implementation\IdMiddleware;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Queue\Middleware\Push\PushRequest;

final class IdMiddlewareTest extends TestCase
{
    public function testWithId(): void
    {
        $message = new Message('test', null, [IdEnvelope::MESSAGE_ID_KEY => 'test-id']);
        $originalRequest = new PushRequest($message, null);
        $handler = $this->createMock(MessageHandlerPushInterface::class);

        $handler->expects($this->once())
            ->method('handlePush')
            ->willReturnArgument(0);

        $middleware = new IdMiddleware();
        $finalRequest = $middleware->processPush($originalRequest, $handler);

        $this->assertSame($originalRequest, $finalRequest);
        $this->assertNotInstanceOf(IdEnvelope::class, $finalRequest->getMessage());
        $this->assertEquals('test-id', $finalRequest->getMessage()->getMetadata()[IdEnvelope::MESSAGE_ID_KEY]);
    }

    public function testWithoutId(): void
    {
        $message = new Message('test', null);
        $originalRequest = new PushRequest($message, null);
        $handler = $this->createMock(MessageHandlerPushInterface::class);

        $handler->expects($this->once())
            ->method('handlePush')
            ->willReturnArgument(0);

        $middleware = new IdMiddleware();
        $finalRequest = $middleware->processPush($originalRequest, $handler);

        $this->assertInstanceOf(IdEnvelope::class, $finalRequest->getMessage());
        $this->assertNotSame($originalRequest, $finalRequest);
        $this->assertNotEmpty($finalRequest->getMessage()->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? null);
    }
}
