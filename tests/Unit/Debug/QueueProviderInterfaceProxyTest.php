<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueConsumerDecorator;
use Yiisoft\Queue\Debug\QueueConsumerProviderProxy;
use Yiisoft\Queue\Debug\QueueProducerProviderProxy;
use Yiisoft\Queue\Debug\QueueProducerDecorator;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;
use Yiisoft\Queue\Provider\QueueProducerProviderInterface;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\QueueProducerInterface;

final class QueueProviderInterfaceProxyTest extends TestCase
{
    public function testProducerProxyDecoratesOnlyProducerRole(): void
    {
        $producer = $this->createMock(QueueProducerInterface::class);
        $provider = $this->createMock(QueueProducerProviderInterface::class);
        $provider->method('getProducer')->willReturn($producer);
        $proxy = new QueueProducerProviderProxy($provider, new QueueCollector());
        self::assertInstanceOf(QueueProducerDecorator::class, $proxy->getProducer('queue'));
    }

    public function testConsumerProxyDelegatesOnlyConsumerRole(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $provider = $this->createMock(QueueConsumerProviderInterface::class);
        $provider->method('getConsumer')->willReturn($consumer);
        $provider->method('hasConsumer')->with('queue')->willReturn(true);
        $provider->method('getConsumerNames')->willReturn(['queue']);
        $proxy = new QueueConsumerProviderProxy($provider, new QueueCollector());
        self::assertInstanceOf(QueueConsumerDecorator::class, $proxy->getConsumer('queue'));
        self::assertTrue($proxy->hasConsumer('queue'));
        self::assertSame(['queue'], $proxy->getConsumerNames());
    }
}
