<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Command\ConsumeCommand;
use Yiisoft\Queue\DefaultQueue;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\Stubs\StubLoop;

final class ConsumeSingleCommandTest extends TestCase
{
    public function testListensToDefaultQueueThroughConsoleApplication(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('listen');
        $consumer->expects($this->never())->method('run');
        $command = new ConsumeCommand(new PredefinedQueueProvider([
            DefaultQueue::NAME => ['consumer' => $consumer],
        ]), new StubLoop());
        $application = new Application();
        $application->setAutoExit(false);
        $application->add($command);

        $result = $application->run(new StringInput('queue:consume'), new BufferedOutput());

        self::assertSame(Command::SUCCESS, $result);
    }

    public function testListensSelectedConsumer(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('listen');
        $command = new ConsumeCommand(new PredefinedQueueProvider(['queue' => ['consumer' => $consumer]]), new StubLoop());
        self::assertSame(0, $command->run(new StringInput('queue'), $this->createMock(OutputInterface::class)));
    }

    public function testRejectsMultipleQueuesWithoutAMode(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->never())->method('listen');
        $consumer->expects($this->never())->method('run');
        $command = new ConsumeCommand(new PredefinedQueueProvider([
            'first' => ['consumer' => $consumer],
            'second' => ['consumer' => $consumer],
        ]), new StubLoop());
        $output = new BufferedOutput();

        $result = $command->run(new StringInput('first second'), $output);

        self::assertSame(Command::INVALID, $result);
        self::assertStringContainsString('Use --all or --once to consume multiple queues.', $output->fetch());
    }
}
