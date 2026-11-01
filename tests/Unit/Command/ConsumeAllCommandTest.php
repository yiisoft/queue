<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Command\ConsumeCommand;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\Stubs\StubQueueProducer;

final class ConsumeAllCommandTest extends TestCase
{
    public function testPollsSelectedQueuesRepeatedlyWithPerQueueLimit(): void
    {
        $calls = [];
        $first = $this->createMock(QueueConsumerInterface::class);
        $first->expects($this->exactly(2))->method('run')->with(3)->willReturnCallback(
            static function () use (&$calls): int {
                $calls[] = 'first';
                return 1;
            },
        );
        $second = $this->createMock(QueueConsumerInterface::class);
        $second->expects($this->exactly(2))->method('run')->with(3)->willReturnCallback(
            static function () use (&$calls): int {
                $calls[] = 'second';
                return 0;
            },
        );
        $other = $this->createMock(QueueConsumerInterface::class);
        $other->expects($this->never())->method('run');
        $command = new ConsumeCommand(new PredefinedQueueProvider([
            'first' => ['consumer' => $first],
            'second' => ['consumer' => $second],
            'other' => ['consumer' => $other],
        ]), $this->createLoop(2));

        $result = $command->run(new StringInput('--all first second --limit=3'), new BufferedOutput());

        self::assertSame(Command::SUCCESS, $result);
        self::assertSame(['first', 'second', 'first', 'second'], $calls);
    }

    public function testOnceWithAllExitsAfterOnePass(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('run')->willReturn(2);
        $consumer->expects($this->never())->method('listen');
        $command = new ConsumeCommand(new PredefinedQueueProvider([
            'consumer' => ['consumer' => $consumer],
        ]), $this->createLoop(2));

        $result = $command->run(new StringInput('--all --once'), new BufferedOutput());

        self::assertSame(Command::SUCCESS, $result);
    }

    public function testReportsWhenNoConsumersAreConfigured(): void
    {
        $command = new ConsumeCommand(new PredefinedQueueProvider([]), $this->createMock(LoopInterface::class));
        $output = new BufferedOutput();

        self::assertSame(Command::SUCCESS, $command->run(new ArrayInput(['--all' => true], $command->getNativeDefinition()), $output));
        self::assertSame("No consumers are configured.\n", $output->fetch());
    }

    public function testRunsOnlyConsumerRolesByDefault(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('run')->willReturn(0);
        $command = new ConsumeCommand(new PredefinedQueueProvider([
            'producer' => ['producer' => new StubQueueProducer()],
            'consumer' => ['consumer' => $consumer],
        ]), $this->createLoop(1));
        $input = new ArrayInput(['--all' => true, '--pause' => 0], $command->getNativeDefinition());
        self::assertSame(Command::SUCCESS, $command->run($input, $this->createMock(OutputInterface::class)));
    }

    public function testDoesNotPauseWhenTheLoopCannotContinue(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->method('run')->willReturn(0);
        $command = new ConsumeCommand(new PredefinedQueueProvider([
            'consumer' => ['consumer' => $consumer],
        ]), $this->createLoop(1));
        $input = new ArrayInput(['--all' => true, '--pause' => 3], $command->getNativeDefinition());

        $start = hrtime(true);
        $command->run($input, $this->createMock(OutputInterface::class));

        self::assertLessThan(1.0, (hrtime(true) - $start) / 1_000_000_000, 'The pause is skipped once the loop asked to stop.');
    }

    public function testPausesWhenThereAreNoMessages(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->method('run')->willReturn(0);
        $command = new ConsumeCommand(new PredefinedQueueProvider([
            'consumer' => ['consumer' => $consumer],
        ]), $this->createLoop(2));
        $input = new ArrayInput(['--all' => true, '--pause' => 1], $command->getNativeDefinition());

        $start = hrtime(true);
        $command->run($input, $this->createMock(OutputInterface::class));

        self::assertGreaterThanOrEqual(1.0, (hrtime(true) - $start) / 1_000_000_000, 'An empty queue is polled once per pause.');
    }

    public function testNegativePauseUsesDefaultIdleDelay(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('run')->willReturn(0);
        $command = new ConsumeCommand(new PredefinedQueueProvider([
            'consumer' => ['consumer' => $consumer],
        ]), $this->createLoop(2));
        $input = new ArrayInput(['--all' => true, '--pause' => -1], $command->getNativeDefinition());

        $start = hrtime(true);
        $result = $command->run($input, new BufferedOutput());

        self::assertSame(Command::SUCCESS, $result);
        self::assertGreaterThanOrEqual(1.0, (hrtime(true) - $start) / 1_000_000_000);
    }

    /**
     * @return LoopInterface A loop which allows the given number of checks and then asks to stop.
     */
    private function createLoop(int $continueTimes): LoopInterface
    {
        $loop = $this->createMock(LoopInterface::class);
        $loop->method('canContinue')->willReturnCallback(static function () use (&$continueTimes): bool {
            $canContinue = $continueTimes > 0;
            $continueTimes--;

            return $canContinue;
        });

        return $loop;
    }
}
