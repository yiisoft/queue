<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Debug;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueDecorator;
use Yiisoft\Queue\Debug\QueueFactoryInterfaceProxy;
use Yiisoft\Queue\QueueFactoryInterface;
use Yiisoft\Queue\QueueInterface;

class QueueFactoryInterfaceProxyTest extends TestCase
{
    public function testGet(): void
    {
        $queueFactory = $this->createMock(QueueFactoryInterface::class);
        $queue = $this->createMock(QueueInterface::class);
        $queueFactory->expects($this->once())->method('get')->willReturn($queue);
        $collector = new QueueCollector();
        $factory = new QueueFactoryInterfaceProxy($queueFactory, $collector);

        $this->assertInstanceOf(QueueDecorator::class, $factory->get());
    }
}
