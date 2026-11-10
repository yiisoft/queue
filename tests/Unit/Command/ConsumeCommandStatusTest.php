<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Command;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\TerminalStatusReporter;
use Yiisoft\Queue\Command\ConsumeCommand;
use Yiisoft\Queue\Provider\PredefinedQueueProvider;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\Stubs\StubLoop;

use function base64_encode;
use function fopen;
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

        $command->run(new StringInput('--all --pause=0 queuea'), $output);

        self::assertSame(
            [
                $this->sequence('working', 'Listening on queue "queuea"'),
                $this->sequence('working', 'Processing queue "queuea"', 'queue/queuea'),
                $this->sequence('done', 'Queue "queuea": 0 messages processed', 'queue/queuea'),
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
                $this->sequence('working', 'Listening on queue "queuea"'),
                $this->sequence('done', 'Stopped listening on queue "queuea"'),
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
                $this->sequence('working', 'Listening on queue "queuea"'),
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
            $this->sequence('done', 'Queue "weird: name": 1 messages processed', 'queue/weird-name'),
            (string) $this->read($stream),
        );
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
