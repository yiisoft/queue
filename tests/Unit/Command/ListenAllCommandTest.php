<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Command\ListenAllCommand;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\Stubs\StubQueueProducer;

final class ListenAllCommandTest extends TestCase
{
    public function testRunsOnlyConsumerRolesByDefault(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('run')->willReturn(0);
        $loop = $this->createMock(LoopInterface::class);
        $loop->method('canContinue')->willReturn(true, false);
        $command = new ListenAllCommand(new PredefinedQueueProvider([
            'producer' => ['producer' => new StubQueueProducer()],
            'consumer' => ['consumer' => $consumer],
        ]), $loop);
        $input = new ArrayInput([], $command->getNativeDefinition());
        $input->setOption('pause', 0);
        self::assertSame(0, $command->run($input, $this->createMock(OutputInterface::class)));
    }
}
