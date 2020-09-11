<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Tests;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Test\Support\EventDispatcher\SimpleEventDispatcher;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Cli\SignalLoop;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Tests\App\ContainerConfigurator;
use Yiisoft\Yii\Queue\Worker\Worker;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

/**
 * Base Test Case.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class TestCase extends BaseTestCase
{
    public Container $container;
    protected ContainerConfigurator $containerConfigurator;

    protected function setUp(): void
    {
        $this->container = new Container(require Builder::path('tests-app'));
        $this->containerConfigurator = new ContainerConfigurator($this->container);
        $eventConfigurator = $this->container->get(EventDispatcherProvider::class);
        $eventConfigurator->register($this->container);
    }

    protected function getQueue(): Queue
    {
        return new Queue(
            $this->getDriver(),
            $this->getEventDispatcher(),
            $this->getWorker(),
            $this->getLoop(),
            new NullLogger()
        );
    }

    protected function getDriver(): DriverInterface
    {
        return $this->createMock(DriverInterface::class);
    }

    protected function getLoop(): LoopInterface
    {
        return new SignalLoop();
    }

    protected function getWorker(): WorkerInterface
    {
        return new Worker(
            $this->getEventHandlers(),
            $this->getEventDispatcher(),
            new NullLogger(),
            new Injector($this->getContainer()),
            $this->getContainer()
        );
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return new SimpleEventDispatcher();
    }

    private function getEventHandlers(): array
    {
        return [];
    }

    protected function getContainer(): ContainerInterface
    {
        return new SimpleContainer($this->getContainerDefinitions());
    }

    protected function getContainerDefinitions(): array
    {
        return [];
    }
}
