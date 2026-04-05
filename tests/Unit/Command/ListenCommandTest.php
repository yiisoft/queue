<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Command\ListenCommand;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueInterface;

final class ListenCommandTest extends TestCase
{
    public function testExecuteWithDefaultQueue(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('listen');

        $queueProvider = $this->createMock(QueueProviderInterface::class);
        $queueProvider->expects($this->once())
            ->method('get')
            ->with($this->equalTo('yii-queue'))
            ->willReturn($queue);

        $input = new StringInput('');
        $command = new ListenCommand($queueProvider);
        $exitCode = $command->run($input, $this->createMock(OutputInterface::class));

        $this->assertEquals(0, $exitCode);
    }

    public function testExecuteWithCustomQueue(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('listen');

        $queueProvider = $this->createMock(QueueProviderInterface::class);
        $queueProvider->expects($this->once())
            ->method('get')
            ->with($this->equalTo('custom-queue'))
            ->willReturn($queue);

        $input = new StringInput('custom-queue');
        $command = new ListenCommand($queueProvider);
        $exitCode = $command->run($input, $this->createMock(OutputInterface::class));

        $this->assertEquals(0, $exitCode);
    }

    public function testExecuteReturnsZero(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('listen');

        $queueProvider = $this->createMock(QueueProviderInterface::class);
        $queueProvider->method('get')->willReturn($queue);

        $input = new StringInput('');
        $command = new ListenCommand($queueProvider);
        $exitCode = $command->run($input, $this->createMock(OutputInterface::class));

        $this->assertSame(0, $exitCode);
    }
}
