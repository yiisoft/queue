<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Command\RunCommand;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\QueueInterface;

final class RunCommandTest extends TestCase
{
    public function testExecuteWithSingleQueue(): void
    {
        $queue = $this->createMockForIntersectionOfInterfaces([QueueInterface::class, QueueConsumerInterface::class]);
        $queue->expects($this->once())
            ->method('run')
            ->with($this->equalTo(0))
            ->willReturn(5);

        $queueProvider = new PredefinedQueueProvider([
            'test-queue' => $queue,
        ]);

        $input = new StringInput('test-queue');
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())
            ->method('write')
            ->with($this->equalTo('Processing queue test-queue... '));
        $output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('Messages processed: 5.'));

        $command = new RunCommand($queueProvider);
        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
    }

    public function testExecuteWithMultipleQueues(): void
    {
        $queue1 = $this->createMockForIntersectionOfInterfaces([QueueInterface::class, QueueConsumerInterface::class]);
        $queue1->expects($this->once())
            ->method('run')
            ->with($this->equalTo(0))
            ->willReturn(3);

        $queue2 = $this->createMockForIntersectionOfInterfaces([QueueInterface::class, QueueConsumerInterface::class]);
        $queue2->expects($this->once())
            ->method('run')
            ->with($this->equalTo(0))
            ->willReturn(7);

        $queueProvider = new PredefinedQueueProvider([
            'queue1' => $queue1,
            'queue2' => $queue2,
        ]);

        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->exactly(2))
            ->method('write');
        $output->expects($this->exactly(2))
            ->method('writeln');

        $input = new StringInput('queue1 queue2');
        $command = new RunCommand($queueProvider);
        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
    }

    public function testExecuteWithLimitOption(): void
    {
        $queue = $this->createMockForIntersectionOfInterfaces([QueueInterface::class, QueueConsumerInterface::class]);
        $queue->expects($this->once())
            ->method('run')
            ->with($this->equalTo(100))
            ->willReturn(10);

        $queueProvider = new PredefinedQueueProvider([
            'test-queue' => $queue,
        ]);

        $input = new StringInput('test-queue --limit=100');
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())
            ->method('write')
            ->with($this->equalTo('Processing queue test-queue... '));
        $output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('Messages processed: 10.'));

        $command = new RunCommand($queueProvider);
        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
    }

    public function testExecuteWithDefaultQueues(): void
    {
        $queue = $this->createMockForIntersectionOfInterfaces([QueueInterface::class, QueueConsumerInterface::class]);
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

    public function testExecuteWithDefaultQueuesSkipsProducerOnlyQueues(): void
    {
        $queue = $this->createMockForIntersectionOfInterfaces([QueueInterface::class, QueueConsumerInterface::class]);
        $queue->expects($this->once())
            ->method('run')
            ->with($this->equalTo(0))
            ->willReturn(2);

        $queueProvider = new PredefinedQueueProvider([
            'producer-only' => $this->createMock(QueueInterface::class),
            'consumer' => $queue,
        ]);

        $input = new StringInput('');
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())
            ->method('write')
            ->with($this->equalTo('Processing queue consumer... '));
        $output->expects($this->once())
            ->method('writeln')
            ->with($this->equalTo('Messages processed: 2.'));

        $command = new RunCommand($queueProvider);
        $exitCode = $command->run($input, $output);

        $this->assertEquals(0, $exitCode);
    }

    public function testExecuteRequiresConsumerQueue(): void
    {
        $queueProvider = new PredefinedQueueProvider([
            'producer-only' => $this->createMock(QueueInterface::class),
        ]);

        $this->expectException(InvalidQueueConfigException::class);
        $this->expectExceptionMessage('Queue "producer-only" must implement');

        $command = new RunCommand($queueProvider);
        $command->run(new StringInput('producer-only'), $this->createMock(OutputInterface::class));
    }
}
