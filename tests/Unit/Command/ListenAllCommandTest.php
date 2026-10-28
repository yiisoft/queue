<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Queue\Cli\LoopInterface;
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
        ]), $this->createStoppingLoop());
        $input = new ArrayInput(['--pause' => 0], $command->getNativeDefinition());
        self::assertSame(Command::SUCCESS, $command->run($input, $this->createMock(OutputInterface::class)));
    }

    public function testDoesNotPauseWhenTheLoopCannotContinue(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->method('run')->willReturn(0);
        $command = new ListenAllCommand(new PredefinedQueueProvider([
            'consumer' => ['consumer' => $consumer],
        ]), $this->createStoppingLoop());
        $input = new ArrayInput(['--pause' => 3], $command->getNativeDefinition());

        $start = hrtime(true);
        $command->run($input, $this->createMock(OutputInterface::class));

        self::assertLessThan(1.0, (hrtime(true) - $start) / 1_000_000_000, 'The pause is skipped once the loop asked to stop.');
    }

    /**
     * @return LoopInterface A loop which allows a single iteration and then asks to stop.
     */
    private function createStoppingLoop(): LoopInterface
    {
        return new class implements LoopInterface {
            private bool $canContinue = true;

            public function canContinue(): bool
            {
                $canContinue = $this->canContinue;
                $this->canContinue = false;

                return $canContinue;
            }
        };
    }
}
