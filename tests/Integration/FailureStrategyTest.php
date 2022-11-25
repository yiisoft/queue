<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Integration;

use InvalidArgumentException;
use Yiisoft\Factory\Factory;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\Implementation\ExponentialDelayMiddleware;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\Implementation\SendAgainMiddleware;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;

class FailureStrategyTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->queue = $this->createMock(QueueInterface::class);
    }

    /**
     * The first strategy must handle the message once, after that the second strategy must handle the message
     * two more times. And after all of them an exception should be thrown.
     */
    public function testComplexStrategyWithMemoryPipelineFactory(): void
    {
        $message = new Message('simple', null, []);
        $queueCallback = static fn (MessageInterface $message): MessageInterface => $message;

        $this->queue->expects(self::exactly(7))->method('push')->willReturnCallback($queueCallback);

        $pipelines = [
            'simple' => [
                new SendAgainMiddleware('test', 1, $this->queue),
                [
                    'class' => SendAgainMiddleware::class,
                    '__construct()' => ['test-factory', 1, $this->queue],
                ],
                [
                    new SendAgainMiddleware('test-callable', 1, $this->queue),
                    'handle',
                ],
                fn (): SendAgainMiddleware => new SendAgainMiddleware('test-callable-2', 1, $this->queue),
                SendAgainMiddleware::class,
                new ExponentialDelayMiddleware(
                    'test',
                    2,
                    1,
                    5,
                    2,
                    $this->createMock(DelayMiddlewareInterface::class),
                    $this->queue,
                ),
            ],
        ];
        $factory = new MemoryPipelineFactory($pipelines, $this->getStrategyFactory());
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
            self::assertEquals(7, $iteration);
        }
    }

    /**
     * The first strategy must handle the message once, after that the second strategy must handle the message
     * two more times. And after all of them an exception should be thrown.
     */
    public function testComplexStrategyWithWeakPipelineFactory(): void
    {
        $message = new Message('simple', null, []);
        $queueCallback = static fn (MessageInterface $message): MessageInterface => $message;

        $this->queue->expects(self::exactly(7))->method('push')->willReturnCallback($queueCallback);

        $pipelines = [
            'simple' => [
                new SendAgainMiddleware('test', 1, $this->queue),
                [
                    'class' => SendAgainMiddleware::class,
                    '__construct()' => ['test-factory', 1, $this->queue],
                ],
                [
                    new SendAgainMiddleware('test-callable', 1, $this->queue),
                    'handle',
                ],
                fn (): SendAgainMiddleware => new SendAgainMiddleware('test-callable-2', 1, $this->queue),
                SendAgainMiddleware::class,
                new ExponentialDelayMiddleware(
                    'test',
                    2,
                    1,
                    5,
                    2,
                    $this->createMock(DelayMiddlewareInterface::class),
                    $this->queue,
                ),
            ],
        ];
        $factory = new WeakPipelineFactory($pipelines, $this->getStrategyFactory());
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
            self::assertEquals(7, $iteration);
        }
    }

    private function getStrategyFactory(): FailureStrategyFactory
    {
        return new FailureStrategyFactory(
            $this->getContainer(),
            new CallableFactory($this->getContainer()),
            new Factory($this->getContainer()),
        );
    }

    protected function getContainerDefinitions(): array
    {
        return [SendAgainMiddleware::class => new SendAgainMiddleware('test-container', 1, $this->queue)];
    }
}
