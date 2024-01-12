<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Command\ListenCommand;
use Yiisoft\Queue\QueueFactoryInterface;
use Yiisoft\Queue\QueueInterface;

final class ListenCommandTest extends TestCase
{
    public function testConfigure(): void
    {
        $command = new ListenCommand($this->createMock(QueueFactoryInterface::class));
        $channelArgument = $command->getNativeDefinition()->getArgument('channel');
        $this->assertEquals('channel', $channelArgument->getName());
    }

    public function testExecute(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())->method('listen');
        $queueFactory = $this->createMock(QueueFactoryInterface::class);
        $queueFactory->method('get')->willReturn($queue);
        $input = new StringInput('channel');

        $command = new ListenCommand($queueFactory);
        $exitCode = $command->run($input, $this->createMock(OutputInterface::class));

        $this->assertEquals(0, $exitCode);
    }
}
