<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Request;
use Yiisoft\Queue\Tests\TestCase;

final class ConsumeRequestTest extends TestCase
{
    public function testImmutable(): void
    {
        $message = new Message('test', 'test');
        $adapter = $this->createMock(AdapterInterface::class);
        $consumeRequest = new Request($message, $adapter);

        $this->assertNotSame($consumeRequest, $consumeRequest->withMessage($message));
        $this->assertNotSame($consumeRequest, $consumeRequest->withAdapter($adapter));
    }
}
