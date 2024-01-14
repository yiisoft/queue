<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling;

use Exception;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\TestCase;

final class FailureHandlingRequestTest extends TestCase
{
    public function testImmutable(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $failureHandlingRequest = new FailureHandlingRequest(
            new Message('test'),
            new Exception(),
            $queue
        );

        $this->assertNotSame($failureHandlingRequest, $failureHandlingRequest->withQueue($queue));
    }
}
