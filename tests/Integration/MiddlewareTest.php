<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\Log\SimpleLogger;
use Yiisoft\Queue\Adapter\SynchronousAdapter;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\MiddlewareFactoryConsume;
use Yiisoft\Queue\Middleware\FailureHandling\FailureFinalHandler;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\ExponentialDelayMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\Implementation\SendAgainMiddleware;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFactoryFailure;
use Yiisoft\Queue\Middleware\Push\Implementation\DelayMiddlewareInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Queue\Queue;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\Integration\Support\TestMiddleware;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Queue\Worker\WorkerInterface;

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
            'message 1',
            'message 2',
        ];

        $pushMiddlewareDispatcher = new PushMiddlewareDispatcher(
            new MiddlewareFactoryPush(
                $this->createMock(ContainerInterface::class),
                new CallableFactory(
                    $this->createMock(ContainerInterface::class),
                ),
            ),
            new TestMiddleware('common 1'),
            new TestMiddleware('common 2'),
        );
        $queue = new Queue(
            $this->createMock(WorkerInterface::class),
            $this->createMock(LoopInterface::class),
            $this->createMock(LoggerInterface::class),
            $pushMiddlewareDispatcher,
            new SynchronousAdapter(
                $this->createMock(WorkerInterface::class),
                $this->createMock(QueueInterface::class),
            ),
        );
        $queue = $queue
            ->withMiddlewares(new TestMiddleware('Won\'t be executed'))
            ->withMiddlewares(new TestMiddleware('channel 1'), new TestMiddleware('channel 2'))
            ->withMiddlewaresAdded(new TestMiddleware('channel 3'));

        $message = new Message('test', ['initial']);
        $messagePushed = $queue->push(
            $message,
            new TestMiddleware('message 1'),
            new TestMiddleware('message 2'),
        );

        self::assertEquals($stack, $messagePushed->getData());
    }

    public function testFullStackConsume(): void
    {
        $stack = [
            'initial',
            'common 1',
            'common 2',
        ];
        $container = new SimpleContainer();
        $callableFactory = new CallableFactory($container);

        $consumeMiddlewareDispatcher = new ConsumeMiddlewareDispatcher(
            new MiddlewareFactoryConsume(
                $this->createMock(ContainerInterface::class),
                new CallableFactory(
                    $this->createMock(ContainerInterface::class),
                ),
            ),
            new TestMiddleware('common 1'),
            new TestMiddleware('common 2'),
        );

        $failureMiddlewareDispatcher = new FailureMiddlewareDispatcher(
            new MiddlewareFactoryFailure($container, $callableFactory),
            [],
        );

        $worker = new Worker(
            ['test' => static fn() => true],
            new SimpleLogger(),
            new Injector($container),
            $container,
            $consumeMiddlewareDispatcher,
            $failureMiddlewareDispatcher,
        );

        $message = new Message('test', ['initial']);
        $messageConsumed = $worker->process($message, $this->createMock(QueueInterface::class));

        self::assertEquals($stack, $messageConsumed->getData());
    }

    public function testFullStackFailure(): void
    {
        $exception = new InvalidArgumentException('test');
        $this->expectExceptionObject($exception);

        $message = new Message('simple', null, []);
        $queueCallback = static fn(MessageInterface $message): MessageInterface => $message;
        $queue = $this->createMock(QueueInterface::class);
        $container = new SimpleContainer([SendAgainMiddleware::class => new SendAgainMiddleware('test-container', 1, $queue)]);
        $callableFactory = new CallableFactory($container);

        $queue->expects(self::exactly(7))->method('push')->willReturnCallback($queueCallback);
        $queue->method('getChannel')->willReturn('simple');

        $middlewares = [
            'simple' => [
                new SendAgainMiddleware('test', 1, $queue),
                [
                    'class' => SendAgainMiddleware::class,
                    '__construct()' => ['test-factory', 1, $queue],
                ],
                [
                    new SendAgainMiddleware('test-callable', 1, $queue),
                    'processFailure',
                ],
                fn(): SendAgainMiddleware => new SendAgainMiddleware('test-callable-2', 1, $queue),
                SendAgainMiddleware::class,
                new ExponentialDelayMiddleware(
                    'test',
                    2,
                    1,
                    5,
                    2,
                    $this->createMock(DelayMiddlewareInterface::class),
                    $queue,
                ),
            ],
        ];
        $dispatcher = new FailureMiddlewareDispatcher(
            new MiddlewareFactoryFailure($container, $callableFactory),
            $middlewares,
        );

        $iteration = 0;
        $request = new FailureHandlingRequest($message, $exception, $queue);
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
