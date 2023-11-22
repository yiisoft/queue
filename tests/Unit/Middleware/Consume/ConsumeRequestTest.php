<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Consume;

use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;

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
