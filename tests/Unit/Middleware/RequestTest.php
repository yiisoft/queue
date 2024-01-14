<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Request;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\TestCase;

final class RequestTest extends TestCase
{
    public function testImmutable(): void
    {
        $message = new Message('test');
        $request = new Request($message, $this->createMock(QueueInterface::class));

        $this->assertNotSame($request, $request->withQueue($this->createMock(QueueInterface::class)));
        $this->assertNotSame($request, $request->withMessage($message));
    }
}
