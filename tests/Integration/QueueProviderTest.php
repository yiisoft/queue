<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Yiisoft\Definitions\Reference;
use Yiisoft\Queue\AsyncQueueProducer;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareFactory;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareFactory;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareConfig;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareFactory;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\Provider\QueueFactoryProvider;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\Stubs\InMemoryAdapter;
use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Queue\Cli\SimpleLoop;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class QueueProviderTest extends TestCase
{
    public function testFactoryProviderResolvesConcreteRolesThroughContainer(): void
    {
        $producer = $this->producer('factory-name');
        $consumer = $this->consumer();
        $container = new SimpleContainer([
            'factory-producer' => $producer,
            'factory-consumer' => $consumer,
        ]);
        $provider = new QueueFactoryProvider([
            'factory-name' => [
                'producer' => Reference::to('factory-producer'),
                'consumer' => Reference::to('factory-consumer'),
            ],
            'producer-only' => ['producer' => Reference::to('factory-producer')],
            'consumer-only' => ['consumer' => Reference::to('factory-consumer')],
        ], $container);

        self::assertSame(['factory-name', 'producer-only'], $provider->getProducerQueueNames());
        self::assertSame(['factory-name', 'consumer-only'], $provider->getConsumerQueueNames());
        self::assertSame('factory-name', $provider->getProducer('factory-name')->getQueueName());
        self::assertInstanceOf(AsyncQueueProducer::class, $provider->getProducer('producer-only'));
        self::assertSame('factory-name', $provider->getProducer('producer-only')->getQueueName());
        self::assertInstanceOf(QueueConsumer::class, $provider->getConsumer('consumer-only'));
        self::assertFalse($provider->hasConsumer('producer-only'));
        self::assertFalse($provider->hasProducer('consumer-only'));

        $this->expectException(QueueNotFoundException::class);
        $provider->getConsumer('producer-only');
    }

    public function testPredefinedProviderExposesConcreteRolesAndNormalizedNames(): void
    {
        $provider = new PredefinedQueueProvider([
            'mixed-name' => ['producer' => $this->producer('mixed-name'), 'consumer' => $this->consumer()],
            'producer-only' => ['producer' => $this->producer('producer-only')],
        ]);

        self::assertSame(['mixed-name', 'producer-only'], $provider->getProducerQueueNames());
        self::assertSame(['mixed-name'], $provider->getConsumerQueueNames());
        self::assertInstanceOf(AsyncQueueProducer::class, $provider->getProducer('mixed-name'));
        self::assertInstanceOf(QueueConsumer::class, $provider->getConsumer('mixed-name'));
        self::assertFalse($provider->hasConsumer('producer-only'));

        $this->expectException(QueueNotFoundException::class);
        $provider->getConsumer('producer-only');
    }

    private function producer(string $name): AsyncQueueProducer
    {
        $container = new SimpleContainer();
        return new AsyncQueueProducer(
            new NullLogger(),
            new PushMiddlewareConfig(new PushMiddlewareFactory($container, new CallableFactory($container))),
            new InMemoryAdapter(),
            $name,
        );
    }

    private function consumer(): QueueConsumer
    {
        $container = new SimpleContainer();
        $worker = new Worker(
            new NullLogger(),
            new ConsumeMiddlewareDispatcher(new ConsumeMiddlewareFactory($container, new CallableFactory($container))),
            new FailureMiddlewareDispatcher(new FailureMiddlewareFactory($container, new CallableFactory($container)), []),
            new HandlerResolver([], $container),
        );
        return new QueueConsumer($worker, new SimpleLoop(), new NullLogger());
    }
}
