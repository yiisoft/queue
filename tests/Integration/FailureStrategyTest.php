<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Integration;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Implementation\DelayMiddlewareInterface;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\DispatcherFactory;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\FailureStrategyFactory;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy\ExponentialDelayStrategy;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy\SendAgainStrategy;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;

class FailureStrategyTest extends TestCase
{
    /**
     * The first strategy must handle the message once, after that the second strategy must handle the message
     * two more times. And after all of them an exception should be thrown.
     */
    public function testComplexStrategy(): void
    {
        $message = new Message('simple', null, []);
        $queueCallback = static fn (MessageInterface $message): MessageInterface => $message;

        $queue = $this->createMock(QueueInterface::class);
        $queue2 = $this->createMock(QueueInterface::class);
        $queue->expects(self::once())->method('push')->willReturnCallback($queueCallback);
        $queue2->expects(self::exactly(2))->method('push')->willReturnCallback($queueCallback);

        $pipelines = [
            'simple' => [
                new SendAgainStrategy('test', 1, $queue),
                new ExponentialDelayStrategy(
                    2,
                    0,
                    5,
                    2,
                    $queue2,
                    $this->createMock(DelayMiddlewareInterface::class)
                ),
            ],
        ];
        $factory = new DispatcherFactory($pipelines, $this->getStrategyFactory());
        $dispatcher = $factory->get('simple');

        $exception = new InvalidArgumentException('test');
        $iteration = 0;
        $request = new ConsumeRequest($message, $this->createMock(QueueInterface::class));
        try {
            do {
                $request = $dispatcher->handle($request, $exception);
                $iteration++;
            } while (true);
        } catch (InvalidArgumentException $thrown) {
            self::assertEquals($exception, $thrown);
            self::assertEquals(3, $iteration);
        }
    }

    private function getStrategyFactory(): FailureStrategyFactory
    {
        return new FailureStrategyFactory(
            $this->getContainer(),
            new CallableFactory($this->getContainer()),
        );
    }
}
