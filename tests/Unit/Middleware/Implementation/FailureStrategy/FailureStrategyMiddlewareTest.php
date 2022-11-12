<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Implementation\FailureStrategy;

use Exception;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\PipelineFactoryInterface;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\FailureStrategyMiddleware;
use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Queue\QueueInterface;

class FailureStrategyMiddlewareTest extends TestCase
{
    public function testUsedOnException(): void
    {
        $pipeline = $this->createMock(PipelineInterface::class);
        $pipeline->expects(self::once())->method('handle')->willReturnArgument(0);

        $handler = $this->createMock(MessageHandlerConsumeInterface::class);
        $handler
            ->expects(self::once())
            ->method('handleConsume')
            ->willThrowException(new Exception('testException'));

        $factory = $this->createMock(PipelineFactoryInterface::class);
        $factory->expects(self::once())->method('get')->willReturn($pipeline);

        $middleware = new FailureStrategyMiddleware($factory);
        $middleware->processConsume(
            new ConsumeRequest(
                $this->createMock(MessageInterface::class),
                $this->createMock(QueueInterface::class)
            ),
            $handler,
        );
    }

    public function testNotUsed(): void
    {
        $handler = $this->createMock(MessageHandlerConsumeInterface::class);
        $handler
            ->expects(self::once())
            ->method('handleConsume')
            ->willReturnArgument(0);

        $factory = $this->createMock(PipelineFactoryInterface::class);
        $factory->expects(self::never())->method('get');

        $middleware = new FailureStrategyMiddleware($factory);
        $middleware->processConsume(
            new ConsumeRequest(
                $this->createMock(MessageInterface::class),
                $this->createMock(QueueInterface::class)
            ),
            $handler,
        );
    }
}
