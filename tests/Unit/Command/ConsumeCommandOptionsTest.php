<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Yiisoft\Queue\Command\ConsumeCommand;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\Stubs\StubLoop;

use function is_array;
use function preg_replace;

final class ConsumeCommandOptionsTest extends TestCase
{
    #[DataProvider('ignoredOptionsProvider')]
    public function testRejectsIgnoredOptionsBeforeResolvingConsumers(string|array $arguments, string $message): void
    {
        $provider = $this->createMock(QueueConsumerProviderInterface::class);
        $provider->expects($this->never())->method('getConsumer');
        $provider->expects($this->never())->method('getConsumerQueueNames');
        $command = new ConsumeCommand($provider, new StubLoop());
        $input = is_array($arguments) ? new ArrayInput($arguments) : new StringInput($arguments);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);

        self::assertSame(Command::INVALID, $result);
        self::assertStringContainsString($message, $output->fetch());
    }

    public static function ignoredOptionsProvider(): iterable
    {
        foreach (['--limit=100', '-m100', '--limit=0', '-m 0', '--limit=0 -- --all'] as $arguments) {
            yield $arguments => [$arguments, '--limit requires --once or --all.'];
        }
        foreach ([
            '--pause=1', '-p1', '--pause=0', '-p 0', '--pause=1 -- --all',
            '--once --pause=1', '--once -p0', '--all --once --pause=0', '--all --once -p 1',
        ] as $arguments) {
            yield $arguments => [$arguments, '--pause requires --all without --once.'];
        }
        yield 'array limit zero' => [['--limit' => 0], '--limit requires --once or --all.'];
        yield 'array short limit zero' => [['-m' => 0], '--limit requires --once or --all.'];
        yield 'array pause default' => [['--pause' => 1], '--pause requires --all without --once.'];
        yield 'array short pause zero' => [['-p' => 0], '--pause requires --all without --once.'];
    }

    #[DataProvider('queueNamesAfterSeparatorProvider')]
    public function testOptionsAfterSeparatorAreQueueNames(string $name): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('listen');
        $command = new ConsumeCommand(new PredefinedQueueProvider([
            $name => ['consumer' => $consumer],
        ]), new StubLoop());

        $result = $command->run(new StringInput('-- ' . $name), new BufferedOutput());

        self::assertSame(Command::SUCCESS, $result);
    }

    public static function queueNamesAfterSeparatorProvider(): iterable
    {
        yield ['--limit=100'];
        yield ['-m100'];
        yield ['--pause=1'];
        yield ['-p1'];
    }

    public function testRejectsIgnoredLimitInGroupedApplicationOptions(): void
    {
        $provider = $this->createMock(QueueConsumerProviderInterface::class);
        $provider->expects($this->never())->method('getConsumer');
        $application = new Application();
        $application->setAutoExit(false);
        $application->add(new ConsumeCommand($provider, new StubLoop()));

        $result = $application->run(new StringInput('queue:consume -vm0'), new BufferedOutput());

        self::assertSame(Command::INVALID, $result);
    }

    public function testCombinedModesAcceptLimitAndPerformOnePass(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('run')->with(2)->willReturn(2);
        $consumer->expects($this->never())->method('listen');
        $command = new ConsumeCommand(new PredefinedQueueProvider([
            'queue' => ['consumer' => $consumer],
        ]), new StubLoop());

        $result = $command->run(new StringInput('--all --once -m2'), new BufferedOutput());

        self::assertSame(Command::SUCCESS, $result);
    }

    public function testHelpExplainsQueueDefaultsAndOncePrecedence(): void
    {
        $application = new Application();
        $application->setAutoExit(false);
        $application->add(new ConsumeCommand(new PredefinedQueueProvider([]), new StubLoop()));
        $output = new BufferedOutput();

        $result = $application->run(new StringInput('queue:consume --help'), $output);

        $help = preg_replace('/\s+/', ' ', $output->fetch());
        self::assertSame(Command::SUCCESS, $result);
        self::assertStringContainsString('Without queue names, listen to yii-queue.', $help);
        self::assertStringContainsString('--once and --all instead use all configured consumer queues.', $help);
        self::assertStringContainsString('--once takes precedence over --all.', $help);
    }
}
