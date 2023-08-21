<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling;

use Exception;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class FailureHandlingRequestTest extends TestCase
{
    public function testImmutable(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $failureHandlingRequest = new FailureHandlingRequest(
            new Message('test', 'test'),
            new Exception(),
            $queue
        );

        $this->assertNotSame($failureHandlingRequest, $failureHandlingRequest->withQueue($queue));
    }
}
