<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Benchmark;

use Generator;
use PhpBench\Attributes\ParamProviders;
use Psr\Log\NullLogger;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\SimpleMessage;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactory;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\Queue;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Tests\Benchmark\Support\VoidAdapter;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class QueueBench
{
    private readonly QueueInterface $queue;
    private readonly JsonMessageSerializer $serializer;
    private readonly VoidAdapter $adapter;

    public function __construct()
    {
        $container = new SimpleContainer();
        $callableFactory = new CallableFactory($container);
        $logger = new NullLogger();

        $worker = new Worker(
            [
                'foo' => static function (): void {},
            ],
            $logger,
            new Injector($container),
            $container,
            new ConsumeMiddlewareDispatcher(new ConsumeMiddlewareFactory($container, $callableFactory)),
            new FailureMiddlewareDispatcher(
                new FailureMiddlewareFactory($container, $callableFactory),
                [],
            ),
            $callableFactory,
        );
        $this->serializer = new JsonMessageSerializer();
        $this->adapter = new VoidAdapter($this->serializer);

        $this->queue = new Queue(
            $worker,
            new SimpleLoop(0),
            $logger,
            new PushMiddlewareConfig(new PushMiddlewareFactory($container, $callableFactory)),
            $this->adapter,
        );
    }

    public function providePush(): Generator
    {
        yield 'simple' => ['message' => new SimpleMessage('foo', 'bar')];
        yield 'with envelopes' => [
            'message' => new FailureEnvelope(
                new IdEnvelope(
                    new SimpleMessage('foo', 'bar'),
                    'test id',
                ),
                ['failure-1' => ['a', 'b', 'c']],
            ),
        ];
    }

    #[ParamProviders('providePush')]
    public function benchPush(array $params): void
    {
        $this->queue->push($params['message']);
    }

    public function provideConsume(): Generator
    {
        yield 'simple mapping' => ['message' => $this->serializer->serialize(new SimpleMessage('foo', 'bar'))];
        yield 'with envelopes mapping' => [
            'message' => $this->serializer->serialize(
                new FailureEnvelope(
                    new IdEnvelope(
                        new SimpleMessage('foo', 'bar'),
                        'test id',
                    ),
                    ['failure-1' => ['a', 'b', 'c']],
                ),
            ),
        ];
    }

    #[ParamProviders('provideConsume')]
    public function benchConsume(array $params): void
    {
        $this->adapter->message = $params['message'];
        $this->queue->run();
    }
}
