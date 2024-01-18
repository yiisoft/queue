<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\TestCase;

final class ConsumeRequestTest extends TestCase
{
    public function testImmutable(): void
    {
        $message = new Message('test', 'test');
        $queue = $this->createMock(QueueInterface::class);
        $consumeRequest = new ConsumeRequest($message, $queue);

        $this->assertNotSame($consumeRequest, $consumeRequest->withMessage($message));
        $this->assertNotSame($consumeRequest, $consumeRequest->withQueue($queue));
    }
}
