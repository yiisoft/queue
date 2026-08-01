<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Yiisoft\Definitions\Reference;
use Yiisoft\Queue\Command\ListenCommand;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueConsumerDecorator;
use Yiisoft\Queue\Debug\QueueConsumerProviderProxy;
use Yiisoft\Queue\Debug\QueueProducerDecorator;
use Yiisoft\Queue\Debug\QueueProducerProviderProxy;
use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\Provider\QueueFactoryProvider;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\QueueProducerInterface;
use Yiisoft\Queue\Stubs\StubQueueConsumer;
use Yiisoft\Queue\Stubs\StubQueueProducer;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class QueueProviderTest extends TestCase
{
    public function testFactoryRoleMapsResolveThroughContainerAndKeepCapabilitiesSeparate(): void
    {
        $container = new SimpleContainer(['producer-name' => 'factory-both']);
        $provider = new QueueFactoryProvider([
            'both' => [
                'producer' => [
                    'class' => StubQueueProducer::class,
                    '__construct()' => ['name' => Reference::to('producer-name')],
                ],
                'consumer' => StubQueueConsumer::class,
            ],
            'producer-only' => ['producer' => StubQueueProducer::class],
            'consumer-only' => ['consumer' => StubQueueConsumer::class],
        ], $container);

        self::assertSame(['both', 'producer-only'], $provider->getProducerNames());
        self::assertSame(['both', 'consumer-only'], $provider->getConsumerNames());
        self::assertSame('factory-both', $provider->getProducer('both')->getName());
        self::assertInstanceOf(StubQueueConsumer::class, $provider->getConsumer('both'));
        self::assertInstanceOf(StubQueueProducer::class, $provider->getProducer('producer-only'));
        self::assertInstanceOf(StubQueueConsumer::class, $provider->getConsumer('consumer-only'));
        self::assertFalse($provider->hasConsumer('producer-only'));
        self::assertFalse($provider->hasProducer('consumer-only'));

        $this->expectException(QueueNotFoundException::class);
        $provider->getConsumer('producer-only');
    }

    public function testPredefinedRoleMapsAndListenCommandUseConsumerOnlyService(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects(self::once())->method('listen');
        $provider = new PredefinedQueueProvider([
            'both' => ['producer' => new StubQueueProducer('predefined-both'), 'consumer' => new StubQueueConsumer()],
            'producer-only' => ['producer' => new StubQueueProducer()],
            'consumer-only' => ['consumer' => $consumer],
        ]);

        self::assertSame(['both', 'producer-only'], $provider->getProducerNames());
        self::assertSame(['both', 'consumer-only'], $provider->getConsumerNames());
        self::assertInstanceOf(QueueProducerInterface::class, $provider->getProducer('both'));
        self::assertInstanceOf(QueueConsumerInterface::class, $provider->getConsumer('both'));
        self::assertFalse($provider->hasConsumer('producer-only'));
        self::assertFalse($provider->hasProducer('consumer-only'));

        self::assertSame(0, (new ListenCommand($provider))->run(new StringInput('consumer-only'), new NullOutput()));

        try {
            $provider->getProducer('consumer-only');
            self::fail('A consumer-only queue must not expose a producer service.');
        } catch (QueueNotFoundException) {
            self::addToAssertionCount(1);
        }
    }

    public function testDebugProxiesPreserveSeparatedProviderRoles(): void
    {
        $provider = new PredefinedQueueProvider([
            'mixed-name' => ['producer' => new StubQueueProducer('mixed-name')],
            'consumer-only' => ['consumer' => new StubQueueConsumer()],
        ]);
        $collector = new QueueCollector();
        $collector->startup();

        $producerProvider = new QueueProducerProviderProxy($provider, $collector);
        $consumerProvider = new QueueConsumerProviderProxy($provider, $collector);
        $producer = $producerProvider->getProducer('mixed-name');
        $consumer = $consumerProvider->getConsumer('consumer-only');
        $producer->push(new GenericMessage('test', 'payload'));

        self::assertInstanceOf(QueueProducerDecorator::class, $producer);
        self::assertInstanceOf(QueueConsumerDecorator::class, $consumer);
        self::assertSame(['mixed-name'], $producerProvider->getProducerNames());
        self::assertSame(['consumer-only'], $consumerProvider->getConsumerNames());
        self::assertSame(1, $collector->getSummary()['countPushes']);
    }
}
