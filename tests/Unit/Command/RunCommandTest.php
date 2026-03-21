<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Command\RunCommand;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueInterface;

final class RunCommandTest extends TestCase
{
    public function testExecuteWithSingleQueue(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('run')
            ->with($this->equalTo(0))
            ->willReturn(5);

        $queueProvider = $this->createMock(QueueProviderInterface::class);
        $queueProvider->expects($this->once())
            ->method('get')
            ->with($this->equalTo('test-queue'))
            ->willReturn($queue);

        $input = new StringInput('test-queue');
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())
            ->method('write')
            ->with($this->equalTo('Processing queue test-queue... '));
        $output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('Messages processed: 5.'));

        $command = new RunCommand($queueProvider, []);
        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
    }

    public function testExecuteWithMultipleQueues(): void
    {
        $queue1 = $this->createMock(QueueInterface::class);
        $queue1->expects($this->once())
            ->method('run')
            ->with($this->equalTo(0))
            ->willReturn(3);

        $queue2 = $this->createMock(QueueInterface::class);
        $queue2->expects($this->once())
            ->method('run')
            ->with($this->equalTo(0))
            ->willReturn(7);

        $queueProvider = $this->createMock(QueueProviderInterface::class);
        $queueProvider->expects($this->exactly(2))
            ->method('get')
            ->willReturnOnConsecutiveCalls($queue1, $queue2);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->exactly(2))
            ->method('write');
        $output->expects($this->exactly(2))
            ->method('writeln');

        $input = new StringInput('queue1 queue2');
        $command = new RunCommand($queueProvider, []);
        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
    }

    public function testExecuteWithMaximumOption(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('run')
            ->with($this->equalTo(100))
            ->willReturn(10);

        $queueProvider = $this->createMock(QueueProviderInterface::class);
        $queueProvider->expects($this->once())
            ->method('get')
            ->with($this->equalTo('test-queue'))
            ->willReturn($queue);

        $input = new StringInput('test-queue --maximum=100');
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())
            ->method('write')
            ->with($this->equalTo('Processing queue test-queue... '));
        $output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('Messages processed: 10.'));

        $command = new RunCommand($queueProvider, []);
        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
    }

    public function testExecuteWithDefaultQueues(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())
            ->method('run')
            ->with($this->equalTo(0))
            ->willReturn(2);

        $queueProvider = new PredefinedQueueProvider([
            QueueProviderInterface::DEFAULT_QUEUE => $queue,
        ]);

        $input = new StringInput('');
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())
            ->method('write')
            ->with($this->equalTo('Processing queue ' . QueueProviderInterface::DEFAULT_QUEUE . '... '));
        $output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('Messages processed: 2.'));

        $command = new RunCommand($queueProvider);
        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
    }
}
