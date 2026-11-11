<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\ScopedLoopInterface;
use Yiisoft\Queue\Cli\TerminalStatusReporter;
use Yiisoft\Queue\Command\ConsumeCommand;
use Yiisoft\Queue\DefaultQueue;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\Provider\QueueNotFoundException;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\Stubs\StubLoop;

use function base64_encode;
use function fopen;
use function hrtime;
use function implode;
use function rewind;
use function str_contains;
use function stream_get_contents;

final class ConsumeCommandStatusTest extends TestCase
{
    public function testOnceSingleQueue(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['queuea' => ['consumer' => $this->consumer(3)]]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once queuea'), $output);

        $bytes = $this->read($stream);
        self::assertSame(
            [
                $this->sequence('working', 'Consuming queue "queuea" in one pass'),
                $this->sequence('working', 'Processing queue "queuea"', 'queue/queuea'),
                $this->sequence('done', 'Queue "queuea": 3 messages processed', 'queue/queuea'),
                $this->sequence('done', '3 messages processed'),
            ],
            $this->statusSequences($bytes),
        );
        self::assertStringContainsString('Processing queue queuea... ', $bytes);
        self::assertStringContainsString('Messages processed: 3.', $bytes);
    }

    public function testOnceMultipleQueues(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider([
                'mail' => ['consumer' => $this->consumer(2)],
                'news' => ['consumer' => $this->consumer(1)],
            ]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once mail news'), $output);

        $bytes = (string) $this->read($stream);
        self::assertStringContainsString($this->sequence('working', 'Consuming 2 queues in one pass'), $bytes);
        self::assertStringContainsString($this->sequence('done', 'Queue "mail": 2 messages processed', 'queue/mail'), $bytes);
        self::assertStringContainsString($this->sequence('done', 'Queue "news": 1 messages processed', 'queue/news'), $bytes);
        self::assertStringContainsString($this->sequence('done', '3 messages processed'), $bytes);
    }

    public function testAllModePasses(): void
    {
        $loop = new class implements LoopInterface {
            private int $checks = 0;

            public function canContinue(): bool
            {
                return $this->checks++ < 3;
            }
        };

        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->expects($this->exactly(2))->method('run')->willReturnOnConsecutiveCalls(0, 1);

        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['queuea' => ['consumer' => $consumer]]),
            $loop,
            $reporter,
        );

        $start = hrtime(true);
        $command->run(new StringInput('--all --pause=0 queuea'), $output);
        $elapsed = (hrtime(true) - $start) / 1_000_000_000;

        self::assertLessThan(0.75, $elapsed, 'A zero pause must not sleep between passes.');
        self::assertSame(
            [
                $this->sequence('working', 'Listening on queue "queuea"'),
                $this->sequence('working', 'Processing queue "queuea"', 'queue/queuea'),
                $this->sequence('done', 'Queue "queuea": 0 messages processed', 'queue/queuea'),
                $this->sequence('idle', 'Queue "queuea" is idle', 'queue/queuea'),
                $this->sequence('working', 'Processing queue "queuea"', 'queue/queuea'),
                $this->sequence('done', 'Queue "queuea": 1 messages processed', 'queue/queuea'),
                $this->sequence('done', 'Stopped listening'),
            ],
            $this->statusSequences($this->read($stream)),
        );
    }

    public function testListenMode(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['queuea' => ['consumer' => $this->consumer(-1)]]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('queuea'), $output);

        self::assertSame(
            [
                $this->sequence('working', 'Listening on queue "queuea"', 'queue/queuea'),
                $this->sequence('done', 'Stopped listening on queue "queuea"', 'queue/queuea'),
            ],
            $this->statusSequences($this->read($stream)),
        );
    }

    public function testRunExceptionReportsErrorStatus(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['queuea' => ['consumer' => $this->consumer(false)]]),
            new StubLoop(),
            $reporter,
        );

        try {
            $command->run(new StringInput('--once queuea'), $output);
            self::fail('The command should rethrow the handler exception.');
        } catch (RuntimeException $exception) {
            self::assertSame('boom', $exception->getMessage());
        }

        self::assertSame(
            [
                $this->sequence('working', 'Consuming queue "queuea" in one pass'),
                $this->sequence('working', 'Processing queue "queuea"', 'queue/queuea'),
                $this->sequence('error', 'Queue "queuea" failed', 'queue/queuea'),
                $this->sequence('error', 'Consume failed'),
            ],
            $this->statusSequences($this->read($stream)),
        );
    }

    public function testListenExceptionReportsErrorStatus(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->method('listen')->willReturnCallback(static fn(): never => throw new RuntimeException('boom'));
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['queuea' => ['consumer' => $consumer]]),
            new StubLoop(),
            $reporter,
        );

        try {
            $command->run(new StringInput('queuea'), $output);
            self::fail('The command should rethrow the handler exception.');
        } catch (RuntimeException $exception) {
            self::assertSame('boom', $exception->getMessage());
        }

        self::assertSame(
            [
                $this->sequence('working', 'Listening on queue "queuea"', 'queue/queuea'),
                $this->sequence('error', 'Queue "queuea" failed', 'queue/queuea'),
                $this->sequence('error', 'Consume on queue "queuea" failed'),
            ],
            $this->statusSequences($this->read($stream)),
        );
    }

    public function testStatusIsSuppressedWithoutAInteractiveStream(): void
    {
        [$command, $output, $stream] = $this->build(['queuea' => ['consumer' => $this->consumer(3)]]);

        $command->run(new StringInput('--once queuea'), $output);

        $bytes = (string) $this->read($stream);
        self::assertFalse(str_contains($bytes, "\x1b]7501"));
        self::assertStringContainsString('Processing queue queuea... Messages processed: 3.', $bytes);
    }

    public function testQueueNameWithoutValidIdCharsFallsBackToRootRecord(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(["!!" => ['consumer' => $this->consumer(1)]]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once "!!"'), $output);

        $bytes = (string) $this->read($stream);
        self::assertStringContainsString($this->sequence('working', 'Processing queue "!!"'), $bytes);
        self::assertStringNotContainsString('id=', $bytes);
    }

    public function testQueueNameWithInvalidCharsIsSanitizedInId(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['weird: name' => ['consumer' => $this->consumer(1)]]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once "weird: name"'), $output);

        self::assertStringContainsString(
            $this->sequence('done', 'Queue "weird: name": 1 messages processed', 'queue/weird-name-1uyugt5'),
            (string) $this->read($stream),
        );
    }

    public function testLongQueueNameIsCutInId(): void
    {
        $queueName = str_repeat('a', 40);
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider([$queueName => ['consumer' => $this->consumer(2)]]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once ' . $queueName), $output);

        self::assertStringContainsString(
            $this->sequence(
                'done',
                'Queue "' . $queueName . '": 2 messages processed',
                'queue/' . str_repeat('a', 23) . '-1jvayw5',
            ),
            (string) $this->read($stream),
        );
    }

    public function testSixDigitDigestIsZeroPadded(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['aa/bb' => ['consumer' => $this->consumer(1)]]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once "aa/bb"'), $output);

        self::assertStringContainsString(
            $this->sequence('done', 'Queue "aa/bb": 1 messages processed', 'queue/aa-bb-0ap1hqi'),
            (string) $this->read($stream),
        );
    }

    public function testThirtyTwoCharQueueNameKeepsItsFullSegment(): void
    {
        $queueName = str_repeat('a', 32);
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider([$queueName => ['consumer' => $this->consumer(1)]]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once ' . $queueName), $output);

        self::assertStringContainsString(
            $this->sequence('done', 'Queue "' . $queueName . '": 1 messages processed', 'queue/' . $queueName),
            (string) $this->read($stream),
        );
    }

    public function testQueueNameWithSlashesIsFlattenedInId(): void
    {
        $queueName = 'a/b/c/d/e/f/g/h/i';
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider([$queueName => ['consumer' => $this->consumer(1)]]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once "' . $queueName . '"'), $output);

        self::assertStringContainsString(
            $this->sequence('done', 'Queue "' . $queueName . '": 1 messages processed', 'queue/a-b-c-d-e-f-g-h-i-1ju46bo'),
            (string) $this->read($stream),
        );
    }

    public function testNamesThatSanitizeToTheSameTextKeepDistinctIds(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider([
                'mail-a' => ['consumer' => $this->consumer(1)],
                'mail:a' => ['consumer' => $this->consumer(2)],
            ]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once "mail-a" "mail:a"'), $output);

        $bytes = (string) $this->read($stream);
        self::assertStringContainsString($this->sequence('done', 'Queue "mail-a": 1 messages processed', 'queue/mail-a'), $bytes);
        self::assertStringContainsString($this->sequence('done', 'Queue "mail:a": 2 messages processed', 'queue/mail-a-1fc5674'), $bytes);
    }

    public function testNonAsciiQueueNameFallsBackToRootRecord(): void
    {
        $queueName = 'почта';
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider([$queueName => ['consumer' => $this->consumer(1)]]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once "' . $queueName . '"'), $output);

        $bytes = (string) $this->read($stream);
        self::assertStringContainsString($this->sequence('working', 'Processing queue "' . $queueName . '"'), $bytes);
        self::assertStringNotContainsString('id=', $bytes);
    }

    public function testOnceWithoutConfiguredQueuesReportsDoneStatus(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(new PredefinedQueueProvider([]), new StubLoop(), $reporter);

        $command->run(new StringInput('--once'), $output);

        self::assertSame(
            [
                $this->sequence('working', 'Consuming all configured queues in one pass'),
                $this->sequence('done', 'No consumers are configured.'),
            ],
            $this->statusSequences($this->read($stream)),
        );
    }

    public function testOnceUnknownQueueReportsErrorStatus(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(new PredefinedQueueProvider([]), new StubLoop(), $reporter);

        try {
            $command->run(new StringInput('--once ghost'), $output);
            self::fail('The command should rethrow the queue exception.');
        } catch (QueueNotFoundException) {
        }

        self::assertSame(
            [
                $this->sequence('working', 'Consuming queue "ghost" in one pass'),
                $this->sequence('error', 'Resolving consumers failed'),
            ],
            $this->statusSequences($this->read($stream)),
        );
    }

    public function testNumericQueueNameIsProcessed(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['123' => ['consumer' => $this->consumer(2)]]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once 123'), $output);

        self::assertStringContainsString(
            $this->sequence('done', 'Queue "123": 2 messages processed', 'queue/123'),
            (string) $this->read($stream),
        );
    }

    public function testNumericQueueNamesFromProviderAreProcessed(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['123' => ['consumer' => $this->consumer(1)]]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once'), $output);

        self::assertStringContainsString(
            $this->sequence('done', 'Queue "123": 1 messages processed', 'queue/123'),
            (string) $this->read($stream),
        );
    }

    public function testDuplicateQueueNamesAreCountedOnce(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider([
                'mail' => ['consumer' => $this->consumer(1)],
                'news' => ['consumer' => $this->consumer(1)],
            ]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once mail mail news'), $output);

        self::assertStringContainsString(
            $this->sequence('working', 'Consuming 2 queues in one pass'),
            (string) $this->read($stream),
        );
    }

    public function testSynchronousModeReportsDoneWithoutListening(): void
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        $consumer->method('isSynchronous')->willReturn(true);

        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['queuea' => ['consumer' => $consumer]]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('queuea'), $output);

        self::assertSame(
            [
                $this->sequence('done', 'Synchronous mode: messages are processed on push', 'queue/queuea'),
            ],
            $this->statusSequences($this->read($stream)),
        );
        self::assertStringContainsString('Synchronous mode', (string) $this->read($stream));
    }

    public function testLoopStartFailureReportsErrorStatus(): void
    {
        $loop = new class implements ScopedLoopInterface {
            public function start(): void
            {
                throw new RuntimeException('loop start failed');
            }

            public function canContinue(): bool
            {
                return true;
            }

            public function finish(): void {}
        };

        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['queuea' => ['consumer' => $this->consumer(3)]]),
            $loop,
            $reporter,
        );

        try {
            $command->run(new StringInput('--once queuea'), $output);
            self::fail('The command should rethrow the loop exception.');
        } catch (RuntimeException $exception) {
            self::assertSame('loop start failed', $exception->getMessage());
        }

        self::assertSame(
            [
                $this->sequence('working', 'Consuming queue "queuea" in one pass'),
                $this->sequence('error', 'Consume failed'),
            ],
            $this->statusSequences($this->read($stream)),
        );
    }

    public function testLoopFinishFailureReportsErrorStatus(): void
    {
        $loop = new class implements ScopedLoopInterface {
            public function start(): void {}

            public function canContinue(): bool
            {
                return true;
            }

            public function finish(): void
            {
                throw new RuntimeException('loop finish failed');
            }
        };

        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['queuea' => ['consumer' => $this->consumer(3)]]),
            $loop,
            $reporter,
        );

        try {
            $command->run(new StringInput('--once queuea'), $output);
            self::fail('The command should rethrow the loop exception.');
        } catch (RuntimeException $exception) {
            self::assertSame('loop finish failed', $exception->getMessage());
        }

        self::assertSame(
            [
                $this->sequence('working', 'Consuming queue "queuea" in one pass'),
                $this->sequence('working', 'Processing queue "queuea"', 'queue/queuea'),
                $this->sequence('done', 'Queue "queuea": 3 messages processed', 'queue/queuea'),
                $this->sequence('error', 'Queue "queuea" failed', 'queue/queuea'),
                $this->sequence('error', 'Consume failed'),
            ],
            $this->statusSequences($this->read($stream)),
        );
    }

    public function testAllModeWithoutPauseOptionSleepsOneSecond(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['queuea' => ['consumer' => $this->consumer(0)]]),
            $this->twoShotLoop(),
            $reporter,
        );

        $start = hrtime(true);
        $command->run(new StringInput('--all'), $output);
        $elapsed = (hrtime(true) - $start) / 1_000_000_000;

        self::assertGreaterThan(0.9, $elapsed, 'The default pause is one second.');
        self::assertLessThan(1.8, $elapsed, 'The default pause is one second.');
    }

    public function testAllModeNegativePauseIsClampedToOneSecond(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['queuea' => ['consumer' => $this->consumer(0)]]),
            $this->twoShotLoop(),
            $reporter,
        );

        $start = hrtime(true);
        $command->run(new StringInput('--all --pause=-5 queuea'), $output);
        $elapsed = (hrtime(true) - $start) / 1_000_000_000;

        self::assertGreaterThan(0.9, $elapsed, 'A negative pause is clamped to one second.');
        self::assertLessThan(1.8, $elapsed, 'A negative pause is clamped to one second.');
    }

    public function testAllModeWithProcessedMessagesDoesNotEmitIdle(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['queuea' => ['consumer' => $this->consumer(1)]]),
            $this->twoShotLoop(),
            $reporter,
        );

        $command->run(new StringInput('--all --pause=0 queuea'), $output);

        self::assertSame(
            [
                $this->sequence('working', 'Listening on queue "queuea"'),
                $this->sequence('working', 'Processing queue "queuea"', 'queue/queuea'),
                $this->sequence('done', 'Queue "queuea": 1 messages processed', 'queue/queuea'),
                $this->sequence('working', 'Processing queue "queuea"', 'queue/queuea'),
                $this->sequence('done', 'Queue "queuea": 1 messages processed', 'queue/queuea'),
                $this->sequence('done', 'Stopped listening'),
            ],
            $this->statusSequences($this->read($stream)),
        );
    }

    public function testUnrepresentableQueueNameIdleDoesNotEmitIdle(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['!!!' => ['consumer' => $this->consumer(0)]]),
            $this->twoShotLoop(),
            $reporter,
        );

        $command->run(new StringInput('--all --pause=0'), $output);

        self::assertSame(
            [
                $this->sequence('working', 'Listening on queue "!!!"'),
                $this->sequence('working', 'Processing queue "!!!"'),
                $this->sequence('done', 'Queue "!!!": 0 messages processed'),
                $this->sequence('done', 'Stopped listening'),
            ],
            $this->statusSequences($this->read($stream)),
        );
    }

    public function testOnceFlagTakesPrecedenceOverAll(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['queuea' => ['consumer' => $this->consumer(3)]]),
            new StubLoop(),
            $reporter,
        );

        $command->run(new StringInput('--once --all queuea'), $output);

        self::assertSame(
            [
                $this->sequence('working', 'Consuming queue "queuea" in one pass'),
                $this->sequence('working', 'Processing queue "queuea"', 'queue/queuea'),
                $this->sequence('done', 'Queue "queuea": 3 messages processed', 'queue/queuea'),
                $this->sequence('done', '3 messages processed'),
            ],
            $this->statusSequences($this->read($stream)),
        );
    }

    public function testFirstExceptionIsRethrownWhenLoopFinishAlsoFails(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['queuea' => ['consumer' => $this->consumer(false)]]),
            $this->twoShotLoop(true),
            $reporter,
        );

        try {
            $command->run(new StringInput('--once queuea'), $output);
            self::fail('The command should rethrow the first exception.');
        } catch (RuntimeException $exception) {
            self::assertSame('boom', $exception->getMessage());
        }

        self::assertSame(
            [
                $this->sequence('working', 'Consuming queue "queuea" in one pass'),
                $this->sequence('working', 'Processing queue "queuea"', 'queue/queuea'),
                $this->sequence('error', 'Queue "queuea" failed', 'queue/queuea'),
                $this->sequence('error', 'Consume failed'),
            ],
            $this->statusSequences($this->read($stream)),
        );
    }

    public function testUnrepresentableQueueNameFailureFallsBackToProgramRecord(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider(['!!!' => ['consumer' => $this->consumer(false)]]),
            new StubLoop(),
            $reporter,
        );

        try {
            $command->run(new StringInput('--once "!!!"'), $output);
            self::fail('The command should rethrow the handler exception.');
        } catch (RuntimeException $exception) {
            self::assertSame('boom', $exception->getMessage());
        }

        self::assertSame(
            [
                $this->sequence('working', 'Consuming queue "!!!" in one pass'),
                $this->sequence('working', 'Processing queue "!!!"'),
                $this->sequence('error', 'Consume failed'),
            ],
            $this->statusSequences($this->read($stream)),
        );
    }

    public function testOptionDescriptionsAndHelpTexts(): void
    {
        $command = new ConsumeCommand(new PredefinedQueueProvider([]), new StubLoop());

        self::assertSame(
            'Continuously poll queues. Without names, use all configured consumer queues. Recommended for development only.',
            $command->getDefinition()->getOption('all')->getDescription(),
        );
        self::assertSame(
            'Without queue names, listen to ' . DefaultQueue::NAME . '. '
                . '--once and --all instead use all configured consumer queues. '
                . '--once takes precedence over --all.',
            $command->getHelp(),
        );
    }

    /**
     * A loop that allows one full pass: the first two `canContinue()` checks pass, the rest do not.
     */
    private function twoShotLoop(bool $finishFails = false): ScopedLoopInterface
    {
        return new class ($finishFails) implements ScopedLoopInterface {
            private int $checks = 0;

            public function __construct(private bool $finishFails) {}

            public function start(): void {}

            public function canContinue(): bool
            {
                return $this->checks++ < 2;
            }

            public function finish(): void
            {
                if ($this->finishFails) {
                    throw new RuntimeException('loop finish failed');
                }
            }
        };
    }

    private function consumer(int|false $count): QueueConsumerInterface
    {
        $consumer = $this->createMock(QueueConsumerInterface::class);
        if ($count === false) {
            $consumer->method('run')->willReturnCallback(static fn(): never => throw new RuntimeException('boom'));
        } else {
            $consumer->method('run')->willReturn($count);
        }

        return $consumer;
    }

    /**
     * @param array<string, array<string, mixed>> $definitions
     *
     * @return array{0: ConsumeCommand, 1: StreamOutput, 2: resource}
     */
    private function build(
        array $definitions,
        ?TerminalStatusReporter $reporter = null,
        ?LoopInterface $loop = null,
    ): array {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $command = new ConsumeCommand(
            new PredefinedQueueProvider($definitions),
            $loop ?? new StubLoop(),
            $reporter,
        );

        return [$command, $output, $stream];
    }

    /** @param resource $stream */
    private function read($stream): string
    {
        rewind($stream);

        return (string) stream_get_contents($stream);
    }

    private function sequence(string $state, string $message, ?string $id = null): string
    {
        $pairs = ['state=' . $state];
        if ($id !== null) {
            $pairs[] = 'id=' . $id;
        }

        return "\x1b]7501;" . implode(':', array_merge($pairs, ['app=queue', 'msg=' . base64_encode($message)])) . "\x1b\\";
    }

    /**
     * Extracts the OSC 7501 sequences from the output bytes, in the order they were written.
     *
     * @return list<string>
     */
    private function statusSequences(string $bytes): array
    {
        $sequences = [];
        $offset = 0;
        while (($start = strpos($bytes, "\x1b]7501;", $offset)) !== false) {
            $end = strpos($bytes, "\x1b\\", $start);
            if ($end === false) {
                self::fail('An OSC 7501 sequence is not terminated in the output.');
            }

            $sequences[] = substr($bytes, $start, 2 + $end - $start);
            $offset = $end + 2;
        }

        return $sequences;
    }
}
