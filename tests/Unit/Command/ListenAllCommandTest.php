<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\ScopedLoopInterface;
use Yiisoft\Queue\Command\ListenAllCommand;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\Stubs\StubQueueProducer;

final class ListenAllCommandTest extends TestCase
{
    public function testReportsWhenNoConsumersAreConfigured(): void
    {
        $command = new ListenAllCommand(new PredefinedQueueProvider([]), $this->createMock(LoopInterface::class));
        $output = new BufferedOutput();

        self::assertSame(Command::SUCCESS, $command->run(new ArrayInput([], $command->getNativeDefinition()), $output));
        self::assertSame("No consumers are configured.\n", $output->fetch());
    }

    public function testRunsOnlyConsumerRolesByDefault(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('run')->willReturn(0);
        $command = new ListenAllCommand(new PredefinedQueueProvider([
            'producer' => ['producer' => new StubQueueProducer()],
            'consumer' => ['consumer' => $consumer],
        ]), $this->createLoop(1));
        $input = new ArrayInput(['--pause' => 0], $command->getNativeDefinition());
        self::assertSame(Command::SUCCESS, $command->run($input, $this->createMock(OutputInterface::class)));
    }

    public function testDoesNotPauseWhenTheLoopCannotContinue(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->method('run')->willReturn(0);
        $command = new ListenAllCommand(new PredefinedQueueProvider([
            'consumer' => ['consumer' => $consumer],
        ]), $this->createLoop(1));
        $input = new ArrayInput(['--pause' => 3], $command->getNativeDefinition());

        $start = hrtime(true);
        $command->run($input, $this->createMock(OutputInterface::class));

        self::assertLessThan(1.0, (hrtime(true) - $start) / 1_000_000_000, 'The pause is skipped once the loop asked to stop.');
    }

    public function testPausesWhenThereAreNoMessages(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->method('run')->willReturn(0);
        $command = new ListenAllCommand(new PredefinedQueueProvider([
            'consumer' => ['consumer' => $consumer],
        ]), $this->createLoop(2));
        $input = new ArrayInput(['--pause' => 1], $command->getNativeDefinition());

        $start = hrtime(true);
        $command->run($input, $this->createMock(OutputInterface::class));

        self::assertGreaterThanOrEqual(1.0, (hrtime(true) - $start) / 1_000_000_000, 'An empty queue is polled once per pause.');
    }

    public function testStartsAndFinishesScopedLoopOnce(): void
    {
        $calls = [];
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->method('run')->willReturnCallback(static function () use (&$calls): int {
            $calls[] = 'run';
            return 0;
        });
        $loop = $this->createMock(ScopedLoopInterface::class);
        $loop->method('start')->willReturnCallback(static function () use (&$calls): void {
            $calls[] = 'start';
        });
        $loop->method('finish')->willReturnCallback(static function () use (&$calls): void {
            $calls[] = 'finish';
        });
        $loop->method('canContinue')->willReturn(true, false, false);
        $command = new ListenAllCommand(new PredefinedQueueProvider([
            'consumer' => ['consumer' => $consumer],
        ]), $loop);
        $input = new ArrayInput(['--pause' => 0], $command->getNativeDefinition());

        $command->run($input, $this->createMock(OutputInterface::class));

        self::assertSame(['start', 'run', 'finish'], $calls);
    }

    /**
     * @return LoopInterface A loop which allows the given number of checks and then asks to stop.
     */
    private function createLoop(int $continueTimes): LoopInterface
    {
        return new class ($continueTimes) implements LoopInterface {
            public function __construct(private int $continueTimes) {}

            public function canContinue(): bool
            {
                return $this->continueTimes-- > 0;
            }
        };
    }
}
