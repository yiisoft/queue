<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Command\RunCommand;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueInterface;

final class RunCommandTest extends TestCase
{
    public function testConfigure(): void
    {
        $command = new RunCommand($this->createMock(QueueProviderInterface::class), []);
        $channelArgument = $command->getNativeDefinition()->getArgument('channel');
        $this->assertEquals('channel', $channelArgument->getName());
    }

    public function testExecute(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())->method('run');
        $queueProvider = $this->createMock(QueueProviderInterface::class);
        $queueProvider->method('get')->willReturn($queue);
        $input = new StringInput('channel');

        $command = new RunCommand($queueProvider, []);
        $exitCode = $command->run($input, $this->createMock(OutputInterface::class));

        $this->assertEquals(0, $exitCode);
    }
}
