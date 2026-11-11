<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use RuntimeException;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\ScopedLoopInterface;
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\Cli\TerminalStatusWriter;
use Yiisoft\Queue\Command\ConsumeCommand;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;
use Yiisoft\Queue\Provider\QueueFactoryProvider;
use Yiisoft\Queue\QueueConsumer;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\Stubs\StubLoop;
use Yiisoft\Queue\Worker\WorkerInterface;

use function array_slice;
use function function_exists;
use function fclose;
use function fopen;
use function getenv;
use function putenv;

use const SIGINT;

final class ConsumeCommandStatusTest extends TestCase
{
    public function testClosedOutputStreamDoesNotPreventListening(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('listen');
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['mail' => ['consumer' => $consumer]]),
            new StubLoop(),
        );
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, decorated: true);
        fclose($stream);
        $setting = getenv('YII_TERMINAL_STATUS');
        putenv('YII_TERMINAL_STATUS=on');

        try {
            self::assertSame(0, $command->run(new StringInput('mail'), $output));
        } finally {
            putenv($setting === false ? 'YII_TERMINAL_STATUS' : 'YII_TERMINAL_STATUS=' . $setting);
        }
    }

    public function testNumericQueueStillConsumesWhenReportingIsDisabled(): void
    {
        $consumer = $this->consumer(1);
        $consumer->expects($this->once())->method('run')->willReturn(1);
        $output = new BufferedOutput();
        $command = new ConsumeCommand(new PredefinedQueueProvider(['123' => ['consumer' => $consumer]]), new StubLoop());
        self::assertSame(0, $command->run(new StringInput('--once 123'), $output));
        self::assertStringContainsString('Messages processed: 1.', $output->fetch());
    }

    public function testDistinctQueuesCannotCollideAndDuplicateNamesAreConsumedOnce(): void
    {
        $names = ['mail:a', 'mail-a', 'mail-a-1fc5674', 'почта', '!!', str_repeat('a', 40), str_repeat('a', 41), '123'];
        $definitions = [];
        foreach ($names as $name) {
            $consumer = $this->createMock(QueueConsumerInterface::class);
            $consumer->expects($this->once())->method('run')->willReturn(1);
            $definitions[$name] = ['consumer' => $consumer];
        }
        [$command, $stream] = $this->command(new PredefinedQueueProvider($definitions));
        $input = '--once ' . implode(' ', $names) . ' mail-a';
        $output = new BufferedOutput();
        self::assertSame(0, $command->run(new StringInput($input), $output));
        $records = $this->records($stream);
        self::assertSame('Consuming 8 queues in one pass', $records[1]['msg']);
        $completed = array_values(array_filter($records, static fn(array $record): bool => $record['state'] === 'done'));
        self::assertCount(9, $completed);
        self::assertCount(9, array_unique(array_column($completed, 'id')));
        self::assertSame('8 messages processed', $completed[8]['msg']);
        foreach ($records as $record) {
            self::assertStringStartsWith('queue/test', $record['id']);
        }
    }

    public function testConfiguredNumericNameIsDiscoveredFromPredefinedProvider(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('run')->willReturn(2);
        $provider = new PredefinedQueueProvider(['123' => ['consumer' => $consumer]]);
        [$command, $stream] = $this->command($provider);

        self::assertSame(0, $command->run(new StringInput('--once'), new BufferedOutput()));
        self::assertSame(['123'], $provider->getConsumerQueueNames());
        self::assertSame('2 messages processed', array_slice($this->records($stream), -1)[0]['msg']);
    }

    public function testConfiguredNumericNameIsDiscoveredFromFactoryProvider(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->once())->method('run')->willReturn(3);
        $provider = new QueueFactoryProvider(['123' => ['consumer' => static fn(): QueueConsumerInterface => $consumer]]);
        [$command, $stream] = $this->command($provider);

        self::assertSame(0, $command->run(new StringInput('--once'), new BufferedOutput()));
        self::assertSame(['123'], $provider->getConsumerQueueNames());
        self::assertSame('3 messages processed', array_slice($this->records($stream), -1)[0]['msg']);
    }

    public function testUserQueueNameHasSingleLineDecodedStatusMessages(): void
    {
        $name = "mail\u{2028}news\u{2029}jobs";
        [$command, $stream] = $this->command(new PredefinedQueueProvider([$name => ['consumer' => $this->consumer(1)]]));

        self::assertSame(0, $command->run(new StringInput('--once "' . $name . '"'), new BufferedOutput()));
        $messages = array_column($this->records($stream), 'msg');
        self::assertContains('Processing queue "mail news jobs"', $messages);
        self::assertContains('Queue "mail news jobs": 1 messages processed', $messages);
    }

    public function testFailingChildReportsErrorBeforeItsInvocation(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->method('run')->willThrowException(new RuntimeException('handler'));
        [$command, $stream] = $this->command(new PredefinedQueueProvider(['mail' => ['consumer' => $consumer]]));
        try {
            $command->run(new StringInput('--once mail'), new BufferedOutput());
            self::fail('Expected the handler failure.');
        } catch (RuntimeException $exception) {
            self::assertSame('handler', $exception->getMessage());
        }
        $records = $this->records($stream);
        self::assertSame(['error', 'error'], array_column(array_slice($records, -2), 'state'));
        self::assertSame(['queue/test/queue-1', 'queue/test'], array_column(array_slice($records, -2), 'id'));
    }

    public function testEnumerationFailureReportsInvocationError(): void
    {
        $provider = $this->createMock(QueueConsumerProviderInterface::class);
        $provider->method('getConsumerQueueNames')->willThrowException(new RuntimeException('enumeration'));
        [$command, $stream] = $this->command($provider);
        try {
            $command->run(new StringInput('--once'), new BufferedOutput());
            self::fail('Expected enumeration failure.');
        } catch (RuntimeException $exception) {
            self::assertSame('enumeration', $exception->getMessage());
        }
        self::assertSame(['working', 'error'], array_column($this->records($stream), 'state'));
    }

    public function testLoopStartupFailureReportsError(): void
    {
        $loop = $this->createMock(ScopedLoopInterface::class);
        $loop->method('start')->willThrowException(new RuntimeException('start'));
        $loop->expects($this->once())->method('finish');
        [$command, $stream] = $this->command(new PredefinedQueueProvider(['mail' => ['consumer' => $this->consumer(1)]]), $loop);
        try {
            $command->run(new StringInput('--once mail'), new BufferedOutput());
            self::fail('Expected startup failure.');
        } catch (RuntimeException $exception) {
            self::assertSame('start', $exception->getMessage());
        }
        self::assertSame('error', array_slice($this->records($stream), -1)[0]['state']);
    }

    public function testLoopCleanupFailurePreventsFinalSuccess(): void
    {
        $loop = $this->createMock(ScopedLoopInterface::class);
        $loop->method('finish')->willThrowException(new RuntimeException('finish'));
        [$command, $stream] = $this->command(new PredefinedQueueProvider(['mail' => ['consumer' => $this->consumer(1)]]), $loop);
        try {
            $command->run(new StringInput('--once mail'), new BufferedOutput());
            self::fail('Expected cleanup failure.');
        } catch (RuntimeException $exception) {
            self::assertSame('finish', $exception->getMessage());
        }
        $records = $this->records($stream);
        self::assertSame('error', array_slice($records, -1)[0]['state']);
        $errors = array_values(array_filter($records, static fn(array $record): bool => $record['state'] === 'error'));
        self::assertSame(['queue/test'], array_column($errors, 'id'));
        self::assertNotContains('1 messages processed', array_column($records, 'msg'));
    }

    public function testCleanupDoesNotReplaceTheOriginalFailure(): void
    {
        $loop = $this->createMock(ScopedLoopInterface::class);
        $loop->method('finish')->willThrowException(new RuntimeException('cleanup'));
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->method('run')->willThrowException(new RuntimeException('handler'));
        [$command] = $this->command(new PredefinedQueueProvider(['mail' => ['consumer' => $consumer]]), $loop);
        $this->expectExceptionMessage('handler');
        $command->run(new StringInput('--once mail'), new BufferedOutput());
    }

    public function testEmptyPersistentPollWaitsAsWorkingAndStopsAsIdle(): void
    {
        $loop = $this->createMock(LoopInterface::class);
        $loop->method('canContinue')->willReturnOnConsecutiveCalls(true, false, false);
        [$command, $stream] = $this->command(new PredefinedQueueProvider(['mail' => ['consumer' => $this->consumer(0)]]), $loop);
        $command->run(new StringInput('--all --pause=0 mail'), new BufferedOutput());
        $records = $this->records($stream);
        self::assertNotContains('done', array_column($records, 'state'));
        self::assertNotContains('blocked', array_column($records, 'state'));
        self::assertSame('Waiting for messages on queue "mail"', $records[3]['msg']);
        self::assertSame('working', $records[3]['state']);
        self::assertSame(['clear', 'idle'], array_column(array_slice($records, -2), 'state'));
    }

    public function testSynchronousConsumerDoesNotClaimToListen(): void
    {
        $consumer = new QueueConsumer($this->createMock(WorkerInterface::class), new StubLoop(), new NullLogger());
        [$command, $stream] = $this->command(new PredefinedQueueProvider(['mail' => ['consumer' => $consumer]]));
        $command->run(new StringInput('mail'), new BufferedOutput());
        $records = $this->records($stream);
        self::assertSame('Consuming queue "mail"', $records[1]['msg']);
        self::assertSame('idle', array_slice($records, -1)[0]['state']);
        self::assertStringNotContainsString('listening', implode(' ', array_column($records, 'msg')));
    }

    public function testInterruptedFinitePassIsNotReportedAsCompleted(): void
    {
        if (!function_exists('pcntl_signal') || !function_exists('posix_kill')) {
            self::markTestSkipped('Process signals are required.');
        }
        $loop = new SignalLoop();
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->method('run')->willReturnCallback(static function (): int {
            posix_kill(getmypid(), SIGINT);
            pcntl_signal_dispatch();
            return 0;
        });
        [$command, $stream] = $this->command(new PredefinedQueueProvider(['mail' => ['consumer' => $consumer]]), $loop);
        $command->run(new StringInput('--once mail'), new BufferedOutput());
        $records = $this->records($stream);
        self::assertNotContains('done', array_column($records, 'state'));
        self::assertSame('idle', array_slice($records, -1)[0]['state']);
    }

    private function consumer(int $count): QueueConsumerInterface
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->method('run')->willReturn($count);
        return $consumer;
    }

    /** @return array{ConsumeCommand, resource} */
    private function command(QueueConsumerProviderInterface $provider, ?LoopInterface $loop = null): array
    {
        $stream = fopen('php://memory', 'w+');
        return [new ConsumeCommand($provider, $loop ?? new StubLoop(), new TerminalStatusWriter($stream, true, 'queue/test')), $stream];
    }

    /**
     * @param resource $stream
     * @return list<array<string, string>>
     */
    private function records($stream): array
    {
        rewind($stream);
        preg_match_all('/\x1b\]7501;([^\x1b]*)\x1b\\\\/', stream_get_contents($stream), $matches);
        $records = [];
        foreach ($matches[1] as $payload) {
            $record = [];
            foreach (explode(':', $payload) as $pair) {
                [$name, $value] = explode('=', $pair, 2);
                $record[$name] = $name === 'msg' ? base64_decode($value) : $value;
            }
            $records[] = $record;
        }
        return $records;
    }
}
