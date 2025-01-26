<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Command\ListenAllCommand;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\QueueInterface;

final class ListenAllCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $queue1 = $this->createMock(QueueInterface::class);
        $queue1->expects($this->once())->method('run');
        $queue2 = $this->createMock(QueueInterface::class);
        $queue2->expects($this->once())->method('run');

        $queueFactory = $this->createMock(QueueProviderInterface::class);
        $queueFactory->method('get')->willReturn($queue1, $queue2);

        $loop = $this->createMock(LoopInterface::class);
        $loop->method('canContinue')->willReturn(true, false);


        $command = new ListenAllCommand(
            $queueFactory,
            $loop,
            ['channel1', 'channel2'],
        );
        $input = new ArrayInput([], $command->getNativeDefinition());
        $input->setOption('pause', 0);
        $exitCode = $command->run($input, $this->createMock(OutputInterface::class));

        $this->assertEquals(0, $exitCode);
    }
}
