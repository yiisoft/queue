<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Queue\Adapter\SynchronousAdapter;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewareFactoryPushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\PushMiddlewareDispatcher;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\QueueFactory;
use Yiisoft\Yii\Queue\Tests\App\FakeAdapter;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

final class QueueFactoryTest extends TestCase
{
    public function testQuickChange(): void
    {
        $worker = $this->createMock(WorkerInterface::class);
        $queue = new Queue(
            $worker,
            $this->createMock(LoopInterface::class),
            $this->createMock(LoggerInterface::class),
            new PushMiddlewareDispatcher($this->createMock(MiddlewareFactoryPushInterface::class)),
        );
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
        $queue = new Queue(
            $worker,
            $this->createMock(LoopInterface::class),
            $this->createMock(LoggerInterface::class),
            new PushMiddlewareDispatcher($this->createMock(MiddlewareFactoryPushInterface::class)),
        );
        $container = $this->createMock(ContainerInterface::class);
        $factory = new QueueFactory(
            [
                'test-channel' => [
                    'class' => FakeAdapter::class,
                    'withChannel()' => ['test-channel'],
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
    }
}
