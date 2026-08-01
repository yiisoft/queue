<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Command\RunCommand;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\Stubs\StubQueueProducer;

final class RunCommandTest extends TestCase
{
    public function testRunsSelectedConsumer(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('run')->with(5)->willReturn(3);
        $command = new RunCommand(new PredefinedQueueProvider(['queue' => ['consumer' => $consumer]]));
        $output = $this->createMock(OutputInterface::class);
        $output->expects($this->once())->method('write')->with('Processing queue queue... ');
        $output->expects($this->once())->method('writeln')->with('Messages processed: 3.');
        self::assertSame(0, $command->run(new StringInput('queue --limit=5'), $output));
    }

    public function testDefaultRunSkipsProducerOnlyQueues(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('run')->willReturn(0);
        $command = new RunCommand(new PredefinedQueueProvider([
            'producer' => ['producer' => new StubQueueProducer()],
            QueueConsumerProviderInterface::DEFAULT_QUEUE => ['consumer' => $consumer],
        ]));
        self::assertSame(0, $command->run(new StringInput(''), $this->createMock(OutputInterface::class)));
    }
}
