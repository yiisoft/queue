<?php

namespace Yiisoft\Yii\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;
use Yiisoft\Yii\Queue\Command\ListenCommand;
use Yiisoft\Yii\Queue\QueueFactoryInterface;
use Yiisoft\Yii\Queue\QueueInterface;

final class ListenCommandTest extends TestCase
{
    public function testConfigure(): void
    {
        $command = new ListenCommand($this->createMock(QueueFactoryInterface::class));
        $chanelArgument = $command->getNativeDefinition()->getArgument('channel');
        $this->assertEquals('channel', $chanelArgument->getName());
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

        $this->assertEquals(ExitCode::OK, $exitCode);
    }
}
