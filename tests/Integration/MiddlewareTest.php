<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\Log\SimpleLogger;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureFinalHandler;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\ExponentialDelayMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\SendAgainMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactory;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\SyncQueueProducer;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Tests\Integration\Support\TestMiddleware;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactoryInterface;

final class MiddlewareTest extends TestCase
{
    public function testFullStackPush(): void
    {
        $stack = [
            'initial',
            'common 1',
            'common 2',
            'channel 1',
            'channel 2',
            'channel 3',
            'channel 4',
        ];

        $pushMiddlewareConfig = new PushMiddlewareConfig(
            new PushMiddlewareFactory(
                $this->createMock(ContainerInterface::class),
            ),
            [
                new TestMiddleware('common 1'),
                new TestMiddleware('common 2'),
            ],
        );
        $worker = new Worker(
            $this->createMock(LoggerInterface::class),
            new ConsumeMiddlewareDispatcher($this->createMock(ConsumeMiddlewareFactoryInterface::class)),
            new FailureMiddlewareDispatcher($this->createMock(FailureMiddlewareFactoryInterface::class), []),
            new HandlerResolver(['test' => static function (): void {}], new SimpleContainer()),
        );
        $queue = new SyncQueueProducer(
            $this->createMock(LoggerInterface::class),
            $pushMiddlewareConfig,
            $worker,
            'test',
            [
                new TestMiddleware('channel 1'),
                new TestMiddleware('channel 2'),
                new TestMiddleware('channel 3'),
                new TestMiddleware('channel 4'),
            ],
        );

        $message = new GenericMessage('test', ['initial']);
        $messagePushed = $queue->push($message);

        self::assertEquals($stack, $messagePushed->getPayload());
    }

    public function testFullStackConsume(): void
    {
        $stack = [
            'initial',
            'common 1',
            'common 2',
        ];
        $container = new SimpleContainer();

        $consumeMiddlewareDispatcher = new ConsumeMiddlewareDispatcher(
            new ConsumeMiddlewareFactory(
                $this->createMock(ContainerInterface::class),
            ),
            new TestMiddleware('common 1'),
            new TestMiddleware('common 2'),
        );

        $failureMiddlewareDispatcher = new FailureMiddlewareDispatcher(
            new FailureMiddlewareFactory($container),
            [],
        );

        $worker = new Worker(
            new SimpleLogger(),
            $consumeMiddlewareDispatcher,
            $failureMiddlewareDispatcher,
            new HandlerResolver(['test' => static fn() => true], $container),
        );

        $message = new GenericMessage('test', ['initial']);
        $messageConsumed = $worker->process($message, 'test-queue');

        self::assertEquals($stack, $messageConsumed->getPayload());
    }

    public function testFullStackFailure(): void
    {
        $exception = new InvalidArgumentException('test');
        $this->expectExceptionObject($exception);

        $message = new GenericMessage('simple', null);
        $queueCallback = static fn(MessageInterface $message): MessageInterface => $message;
        $container = new SimpleContainer([
            SendAgainMiddleware::class => new SendAgainMiddleware('test-container', 1, $queueCallback),
        ]);

        $middlewares = [
            'test-queue' => [
                new SendAgainMiddleware('test', 1, $queueCallback),
                [
                    'class' => SendAgainMiddleware::class,
                    '__construct()' => ['test-factory', 1, $queueCallback],
                ],
                [
                    new SendAgainMiddleware('test-callable', 1, $queueCallback),
                    'processFailure',
                ],
                fn(): SendAgainMiddleware => new SendAgainMiddleware('test-callable-2', 1, $queueCallback),
                SendAgainMiddleware::class,
                new ExponentialDelayMiddleware(
                    'test',
                    2,
                    1,
                    5,
                    2,
                    $queueCallback,
                ),
            ],
        ];
        $dispatcher = new FailureMiddlewareDispatcher(
            new FailureMiddlewareFactory($container),
            $middlewares,
        );

        $iteration = 0;
        $request = new FailureHandlingRequest($message, $exception, 'test-queue', $queueCallback);
        $finalHandler = new FailureFinalHandler();
        try {
            do {
                $request = $dispatcher->dispatch($request, $finalHandler);
                $iteration++;
            } while (true);
        } catch (InvalidArgumentException $thrown) {
            self::assertEquals(7, $iteration);

            throw $thrown;
        }
    }
}
