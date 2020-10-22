<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Integrational;

use RuntimeException;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\FailureStrategy\Dispatcher\DispatcherFactory;
use Yiisoft\Yii\Queue\FailureStrategy\Strategy\ExponentialDelayStrategy;
use Yiisoft\Yii\Queue\FailureStrategy\Strategy\SendAgainStrategy;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;
use Yiisoft\Yii\Queue\PayloadFactory;
use Yiisoft\Yii\Queue\Queue;
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
        $payloadFactory = new PayloadFactory();

        $queueCallback = function (PayloadInterface $payload) use (&$message, $payloadFactory): ?string {
            $message = $payloadFactory->createMessage($payload);

            return null;
        };
        $queue = $this->createMock(Queue::class);
        $queue2 = $this->createMock(Queue::class);
        $queue->expects(self::once())->method('push')->willReturnCallback($queueCallback);
        $queue2->expects(self::exactly(2))->method('push')->willReturnCallback($queueCallback);

        $pipelines = [
            'simple' => [
                new SendAgainStrategy('test', 1, $queue, $payloadFactory),
                new ExponentialDelayStrategy(
                    2,
                    0,
                    5,
                    2,
                    $payloadFactory,
                    $queue2
                ),
            ],
        ];
        $factory = new DispatcherFactory($pipelines, $this->getContainer());
        $dispatcher = $factory->get('simple');

        $exception = new RuntimeException('test');
        $iteration = 0;
        do {
            $event = new JobFailure($this->createMock(Queue::class), $message, $exception);
            $dispatcher->handle($event);
            $iteration++;
        } while ($event->shouldThrowException() === false);

        self::assertEquals(4, $iteration);
    }
}
