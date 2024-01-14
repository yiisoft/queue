<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Adapter\SynchronousAdapter;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\MiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Queue;
use Yiisoft\Queue\QueueFactory;
use Yiisoft\Queue\QueueFactoryInterface;
use Yiisoft\Queue\Tests\App\FakeAdapter;
use Yiisoft\Queue\Worker\WorkerInterface;

final class QueueFactoryTest extends TestCase
{
    public function testQuickChange(): void
    {
        $worker = $this->createMock(WorkerInterface::class);
        $queue = $this->getDefaultQueue($worker);
        $container = $this->createMock(ContainerInterface::class);
        $factory = new QueueFactory(
            [],
            $queue,
            $container,
            new CallableFactory($container),
            new Injector($container),
            true,
            new SynchronousAdapter($worker, $queue)
        );

        $adapter = $factory->get('test-channel');

        self::assertEquals('test-channel', $adapter->getChannelName());
    }

    public function testConfiguredChange(): void
    {
        $worker = $this->createMock(WorkerInterface::class);
        $queue = $this->getDefaultQueue($worker);
        $container = $this->createMock(ContainerInterface::class);
        $factory = new QueueFactory(
            [
                'test-channel' => [
                    'class' => FakeAdapter::class,
                    'withChannel()' => ['test-channel'],
                ],
                QueueFactoryInterface::DEFAULT_CHANNEL_NAME => [
                    'class' => FakeAdapter::class,
                    'withChannel()' => [QueueFactoryInterface::DEFAULT_CHANNEL_NAME],
                ],
            ],
            $queue,
            $container,
            new CallableFactory($container),
            new Injector($container),
            true,
            new SynchronousAdapter($worker, $queue)
        );
        $queue = $factory->get('test-channel');

        self::assertEquals('test-channel', $queue->getChannelName());
        self::assertEquals(QueueFactoryInterface::DEFAULT_CHANNEL_NAME, $factory->get()->getChannelName());
    }

    private function getDefaultQueue(WorkerInterface $worker): Queue
    {
        return new Queue(
            $worker,
            $this->createMock(LoopInterface::class),
            $this->createMock(LoggerInterface::class),
            new MiddlewareDispatcher($this->createMock(MiddlewareFactoryInterface::class)),
        );
    }
}
