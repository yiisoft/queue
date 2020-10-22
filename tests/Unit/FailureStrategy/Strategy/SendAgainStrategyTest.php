<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\FailureStrategy\Strategy;

use Yiisoft\Yii\Queue\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\FailureStrategy\Strategy\SendAgainStrategy;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\PayloadFactory;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\TestCase;

class SendAgainStrategyTest extends TestCase
{
    public function testReturnTrueFromPipeline(): void
    {
        $message = new Message('test', null, [SendAgainStrategy::META_KEY_RESEND . '-test' => 2]);
        $strategy = new SendAgainStrategy('test', 1, $this->createMock(Queue::class), $this->createMock(PayloadFactory::class));
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::once())->method('handle')->willReturn(true);
        $result = $strategy->handle($message, $pipeline);

        self::assertTrue($result);
    }

    public function testReturnFalseFromPipeline(): void
    {
        $message = new Message('test', null, [SendAgainStrategy::META_KEY_RESEND . '-test' => 2]);
        $strategy = new SendAgainStrategy('test', 1, $this->createMock(Queue::class), $this->createMock(PayloadFactory::class));
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::once())->method('handle')->willReturn(false);
        $result = $strategy->handle($message, $pipeline);

        self::assertFalse($result);
    }

    public function testReturnFalseWithoutPipeline(): void
    {
        $message = new Message('test', null, [SendAgainStrategy::META_KEY_RESEND . '-test' => 2]);
        $strategy = new SendAgainStrategy('test', 1, $this->createMock(Queue::class), $this->createMock(PayloadFactory::class));
        $result = $strategy->handle($message, null);

        self::assertFalse($result);
    }
}
