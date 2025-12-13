<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueDecorator;
use Yiisoft\Queue\Debug\QueueProviderInterfaceProxy;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueInterface;

class QueueProviderInterfaceProxyTest extends TestCase
{
    public function testGet(): void
    {
        $queueFactory = $this->createMock(QueueProviderInterface::class);
        $queue = $this->createMock(QueueInterface::class);
        $queueFactory->expects($this->once())->method('get')->willReturn($queue);
        $collector = new QueueCollector();
        $factory = new QueueProviderInterfaceProxy($queueFactory, $collector);

        $this->assertInstanceOf(QueueDecorator::class, $factory->get('test'));
    }

    public function testHas(): void
    {
        $queueFactory = $this->createMock(QueueProviderInterface::class);
        $queueFactory->expects($this->once())->method('has')->with('test')->willReturn(true);
        $collector = new QueueCollector();
        $factory = new QueueProviderInterfaceProxy($queueFactory, $collector);

        $this->assertTrue($factory->has('test'));
    }
}
