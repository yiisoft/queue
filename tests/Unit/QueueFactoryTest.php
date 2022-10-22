<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Yiisoft\Factory\Factory;
use Yiisoft\Test\Support\Container\SimpleContainer;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\ChannelIncorrectlyConfigured;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\ChannelNotConfiguredException;
use Yiisoft\Yii\Queue\QueueFactory;
use Yiisoft\Yii\Queue\QueueInterface;

class QueueFactoryTest extends TestCase
{
    public function testRuntimeDefinitionSuccessful(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue
            ->expects(self::once())
            ->method('withAdapter');

        $adapter = $this->createMock(AdapterInterface::class);
        $adapter
            ->expects(self::once())
            ->method('withChannel')
            ->willReturn($adapter);

        $factory = new QueueFactory(
            [],
            $queue,
            $this->createYiiFactory(),
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
            $this->createYiiFactory(),
            true
        );
    }

    public function testThrowExceptionOnEmptyDefinition(): void
    {
        $this->expectException(ChannelNotConfiguredException::class);

        $queue = $this->createMock(QueueInterface::class);
        $factory = new QueueFactory(
            [],
            $queue,
            $this->createYiiFactory(),
            false
        );

        $factory->get('test');
    }

    public function testThrowExceptionOnIncorrectDefinition(): void
    {
        $this->expectException(ChannelIncorrectlyConfigured::class);

        $queue = $this->createMock(QueueInterface::class);
        $factory = new QueueFactory(
            ['test' => new stdClass()],
            $queue,
            $this->createYiiFactory(),
            false
        );

        $factory->get('test');
    }

    public function testSuccessfulDefinitionWithDefaultAdapter(): void
    {
        $adapterDefault = $this->createMock(AdapterInterface::class);
        $adapterNew = $this->createMock(AdapterInterface::class);

        $queue = $this->createMock(QueueInterface::class);
        $queue
            ->expects(self::once())
            ->method('withAdapter')
            ->with($adapterNew)
            ->willReturn($queue);

        $factory = new QueueFactory(
            ['test' => $adapterNew],
            $queue,
            $this->createYiiFactory(),
            false,
            $adapterDefault
        );

        $factory->get('test');
    }

    public function testSuccessfulDefinitionWithoutDefaultAdapter(): void
    {
        $adapterNew = $this->createMock(AdapterInterface::class);

        $queue = $this->createMock(QueueInterface::class);
        $queue
            ->expects(self::once())
            ->method('withAdapter')
            ->with($adapterNew)
            ->willReturn($queue);

        $factory = new QueueFactory(
            ['test' => $adapterNew],
            $queue,
            $this->createYiiFactory(),
            false
        );

        $factory->get('test');
    }

    private function createYiiFactory(): Factory
    {
        return new Factory(new SimpleContainer());
    }
}
