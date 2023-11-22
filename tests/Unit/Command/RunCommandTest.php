<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Queue\Command\RunCommand;
use Yiisoft\Yii\Queue\QueueFactoryInterface;
use Yiisoft\Yii\Queue\QueueInterface;

final class RunCommandTest extends TestCase
{
    public function testConfigure(): void
    {
        $command = new RunCommand($this->createMock(QueueFactoryInterface::class));
        $channelArgument = $command->getNativeDefinition()->getArgument('channel');
        $this->assertEquals('channel', $channelArgument->getName());
    }

    public function testExecute(): void
    {
        $queue = $this->createMock(QueueInterface::class);
        $queue->expects($this->once())->method('run');
        $queueFactory = $this->createMock(QueueFactoryInterface::class);
        $queueFactory->method('get')->willReturn($queue);
        $input = new StringInput('channel');

        $command = new RunCommand($queueFactory);
        $exitCode = $command->run($input, $this->createMock(OutputInterface::class));

        $this->assertSame(0, $exitCode);
    }
}
