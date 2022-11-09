<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\FailureStrategy\Strategy;

use Exception;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy\ExponentialDelayStrategy;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy\SendAgainStrategy;
use Yiisoft\Yii\Queue\PayloadFactory;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;

class SendAgainStrategyTest extends TestCase
{
    public function testSuccess(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $request = new ConsumeRequest(new Message('test', null), $queue);

        $strategy = new SendAgainStrategy('test', 1, $queue);
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::never())->method('handle');
        $result = $strategy->handle($request, new Exception('test'), $pipeline);

        self::assertInstanceOf(ConsumeRequest::class, $result);
        self::assertArrayHasKey(SendAgainStrategy::META_KEY_RESEND . '-test', $result->getMessage()->getMetadata());
    }

    public function testFailure(): void
    {
        $this->expectExceptionMessage('test');

        $message = new Message('test', null, [SendAgainStrategy::META_KEY_RESEND . '-test' => 1]);
        $queue = $this->createMock(QueueInterface::class);
        $request = new ConsumeRequest($message, $queue);

        $exception = new Exception('test');
        $strategy = new SendAgainStrategy('test', 1, $queue);
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::once())->method('handle')->willThrowException($exception);
        $strategy->handle($request, $exception, $pipeline);
    }
}
