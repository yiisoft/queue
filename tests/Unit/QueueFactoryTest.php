<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use stdClass;
use Yiisoft\Injector\Injector;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Exception\AdapterConfiguration\ChannelIncorrectlyConfigured;
use Yiisoft\Queue\Exception\AdapterConfiguration\ChannelNotConfiguredException;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\QueueFactory;
use Yiisoft\Queue\QueueInterface;

class QueueFactoryTest extends TestCase
{
    public function testRuntimeDefinitionSuccessful(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue
            ->expects(self::once())
            ->method('withAdapter')
            ->willReturn($queue);
        $queue
            ->expects(self::once())
            ->method('withChannelName')
            ->willReturn($queue);

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter
            ->expects(self::once())
            ->method('withChannel')
            ->willReturn($adapter);

        $factory = new QueueFactory(
            [],
            $queue,
            $this->getContainer(),
            new CallableFactory($this->getContainer()),
            new Injector($this->getContainer()),
            true,
            $adapter
        );

        $factory->get('test');
    }

    public function testThrowExceptionOnEmptyAdapter(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Either $enableRuntimeChannelDefinition must be false, or $defaultAdapter should be provided.'
        );

        new QueueFactory(
            [],
            $this->createMock(QueueInterface::class),
            $this->getContainer(),
            new CallableFactory($this->getContainer()),
            new Injector($this->getContainer()),
            true
        );
    }

    public function testThrowExceptionOnEmptyDefinition(): void
    {
        try {
            $queue = $this->createMock(QueueInterface::class);
            $factory = new QueueFactory(
                [],
                $queue,
                $this->getContainer(),
                new CallableFactory($this->getContainer()),
                new Injector($this->getContainer()),
                false
            );

            $factory->get('test');
        } catch (ChannelNotConfiguredException $exception) {
            self::assertSame($exception::class, ChannelNotConfiguredException::class);
            self::assertSame($exception->getName(), 'Queue channel is not properly configured');
            $this->assertMatchesRegularExpression('/"test"/', $exception->getSolution());
        }
    }

    public function testThrowExceptionOnIncorrectDefinition(): void
    {
        try {
            $queue = $this->createMock(QueueInterface::class);
            $factory = new QueueFactory(
                ['test' => new stdClass()],
                $queue,
                $this->getContainer(),
                new CallableFactory($this->getContainer()),
                new Injector($this->getContainer()),
                false
            );

            $factory->get('test');
        } catch (ChannelIncorrectlyConfigured $exception) {
            self::assertSame($exception::class, ChannelIncorrectlyConfigured::class);
            self::assertSame($exception->getName(), 'Incorrect queue channel configuration');
            $this->assertMatchesRegularExpression('/"test"/', $exception->getSolution());
        }
    }

    public function testSuccessfulDefinitionWithDefaultAdapter(): void
    {
        $adapterDefault = $this->createMock(AdapterInterface::class);
        $adapterDefault->method('withChannel')->willReturn($adapterDefault);

        $adapterNew = $this->createMock(AdapterInterface::class);
        $adapterNew->method('withChannel')->willReturn($adapterNew);

        $queue = $this->createMock(QueueInterface::class);
        $queue
            ->expects(self::once())
            ->method('withAdapter')
            ->with($adapterNew)
            ->willReturn($queue);
        $queue
            ->expects(self::once())
            ->method('withChannelName')
            ->with('test')
            ->willReturn($queue);

        $factory = new QueueFactory(
            ['test' => $adapterNew],
            $queue,
            $this->getContainer(),
            new CallableFactory($this->getContainer()),
            new Injector($this->getContainer()),
            false,
            $adapterDefault
        );

        $factory->get('test');
    }

    public function testSuccessfulDefinitionWithoutDefaultAdapter(): void
    {
        $adapterNew = $this->createMock(AdapterInterface::class);
        $adapterNew->method('withChannel')->willReturn($adapterNew);

        $queue = $this->createMock(QueueInterface::class);
        $queue
            ->expects(self::once())
            ->method('withAdapter')
            ->with($adapterNew)
            ->willReturn($queue);
        $queue
            ->expects(self::once())
            ->method('withChannelName')
            ->with('test')
            ->willReturn($queue);

        $factory = new QueueFactory(
            ['test' => $adapterNew],
            $queue,
            $this->getContainer(),
            new CallableFactory($this->getContainer()),
            new Injector($this->getContainer()),
            false
        );

        $factory->get('test');
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new SimpleContainer($instances);
    }
}
