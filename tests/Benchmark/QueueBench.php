<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Benchmark;

use Generator;
use PhpBench\Attributes\ParamProviders;
use PhpBench\Model\Tag;
use Psr\Log\NullLogger;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\JsonMessageSerializer;
use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\MiddlewareFactoryConsume;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFactoryFailure;
use Yiisoft\Queue\Middleware\Push\MiddlewareFactoryPush;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareDispatcher;
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
                'foo' => static function (): void {
                },
            ],
            $logger,
            new Injector($container),
            $container,
            new ConsumeMiddlewareDispatcher(new MiddlewareFactoryConsume($container, $callableFactory)),
            new FailureMiddlewareDispatcher(
                new MiddlewareFactoryFailure($container, $callableFactory),
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
            new PushMiddlewareDispatcher(new MiddlewareFactoryPush($container, $callableFactory)),
            $this->adapter,
        );
    }

    public function providePush(): Generator
    {
        yield 'simple' => ['message' => new Message('foo', 'bar')];
        yield 'with envelopes' => [
            'message' => new FailureEnvelope(
                new IdEnvelope(
                    new Message('foo', 'bar'),
                    'test id',
                ),
                ['failure-1' => ['a', 'b', 'c']],
            ),
        ];
    }

    #[ParamProviders('providePush')]
    #[Tag('queue_push')]
    public function benchPush(array $params): void
    {
        $this->queue->push($params['message']);
    }

    public function provideConsume(): Generator
    {
        yield 'simple mapping' => ['message' => $this->serializer->serialize(new Message('foo', 'bar'))];
        yield 'with envelopes mapping' => [
            'message' => $this->serializer->serialize(
                new FailureEnvelope(
                    new IdEnvelope(
                        new Message('foo', 'bar'),
                        'test id',
                    ),
                    ['failure-1' => ['a', 'b', 'c']],
                ),
            ),
        ];
    }

    #[ParamProviders('provideConsume')]
    #[Tag('queue_consume')]
    public function benchConsume(array $params): void
    {
        $this->adapter->message = $params['message'];
        $this->queue->run();
    }
}
