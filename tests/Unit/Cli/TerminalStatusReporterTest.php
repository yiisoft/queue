<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Cli;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Yiisoft\Queue\Cli\TerminalStatusReporter;

use function base64_encode;
use function fopen;
use function getenv;
use function putenv;
use function rewind;
use function stream_get_contents;

final class TerminalStatusReporterTest extends TestCase
{
    private bool $hadEnv = false;
    private string $savedEnvValue = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->hadEnv = getenv('YII_QUEUE_STATUS') !== false;
        $this->savedEnvValue = (string) getenv('YII_QUEUE_STATUS');
        putenv('YII_QUEUE_STATUS');
    }

    protected function tearDown(): void
    {
        if ($this->hadEnv) {
            putenv('YII_QUEUE_STATUS=' . $this->savedEnvValue);
        } else {
            putenv('YII_QUEUE_STATUS');
        }

        parent::tearDown();
    }

    public function testWorkingReport(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $reporter->emit('working', 'Processing');

        self::assertSame(
            "\x1b]7501;state=working:app=queue:msg=" . base64_encode('Processing') . "\x1b\\",
            $read(),
        );
    }

    public function testOtherStates(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $reporter->emit('idle', 'Waiting');
        $reporter->emit('done', 'Finished');
        $reporter->emit('error', 'Failed');

        self::assertSame(
            "\x1b]7501;state=idle:app=queue:msg=" . base64_encode('Waiting') . "\x1b\\"
            . "\x1b]7501;state=done:app=queue:msg=" . base64_encode('Finished') . "\x1b\\"
            . "\x1b]7501;state=error:app=queue:msg=" . base64_encode('Failed') . "\x1b\\",
            $read(),
        );
    }

    public function testReportWithIdAndProgress(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $reporter->emit('working', 'Processing queue a', 'queue/a', 50);

        self::assertSame(
            "\x1b]7501;state=working:id=queue/a:progress=50:app=queue:msg=" . base64_encode('Processing queue a') . "\x1b\\",
            $read(),
        );
    }

    public function testBlockedReportWithKind(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $reporter->emit('blocked', 'Awaiting input', null, null, 'permission');

        self::assertSame(
            "\x1b]7501;state=blocked:kind=permission:app=queue:msg=" . base64_encode('Awaiting input') . "\x1b\\",
            $read(),
        );
    }

    public function testControlCharactersAreReplacedBySpaces(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $reporter->emit('working', "a\x01b\x1fc");

        self::assertSame(
            "\x1b]7501;state=working:app=queue:msg=" . base64_encode('a b c') . "\x1b\\",
            $read(),
        );
    }

    public function testC1ControlCharactersAreReplacedBySpaces(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $reporter->emit('working', "\xC2\x80x\xC2\x9F");

        self::assertSame(
            "\x1b]7501;state=working:app=queue:msg=" . base64_encode('x') . "\x1b\\",
            $read(),
        );
    }

    public function testControlCharacterRunsBecomeSingleSpace(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $reporter->emit('working', "a\x00\x00b");

        self::assertSame(
            "\x1b]7501;state=working:app=queue:msg=" . base64_encode('a b') . "\x1b\\",
            $read(),
        );
    }

    public function testUtf16SurrogateSequenceIsReplacedBySpace(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $reporter->emit('working', "a\xED\xA0\x80b");

        self::assertSame(
            "\x1b]7501;state=working:app=queue:msg=" . base64_encode('a b') . "\x1b\\",
            $read(),
        );
    }

    public function testEmptyMessageIsOmitted(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $reporter->emit('working', "\x00\x01");

        self::assertSame("\x1b]7501;state=working:app=queue\x1b\\", $read());
    }

    public function testLongMessageIsCut(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $message = str_repeat('a', 2100);
        $reporter->emit('working', $message);

        self::assertSame(
            "\x1b]7501;state=working:app=queue:msg=" . base64_encode(substr($message, 0, 2048)) . "\x1b\\",
            $read(),
        );
    }

    public function testLongMessageIsCutOnACharacterBoundary(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $message = str_repeat('a', 2047) . '€';
        $reporter->emit('working', $message);

        self::assertSame(
            "\x1b]7501;state=working:app=queue:msg=" . base64_encode(str_repeat('a', 2047)) . "\x1b\\",
            $read(),
        );
    }

    public function testMessageCutIsTrimmedAfterTheBoundary(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $reporter->emit('working', str_repeat('a', 2047) . ' c');

        self::assertSame(
            "\x1b]7501;state=working:app=queue:msg=" . base64_encode(str_repeat('a', 2047)) . "\x1b\\",
            $read(),
        );
    }

    public function testCustomApp(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, 'custom-app', true);

        $reporter->emit('working', 'Working');

        self::assertSame(
            "\x1b]7501;state=working:app=custom-app:msg=" . base64_encode('Working') . "\x1b\\",
            $this->read($stream),
        );
    }

    public function testInvalidState(): void
    {
        [, $reporter] = $this->enabledOutput();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be one of: idle, working, done, error, blocked');

        $reporter->emit('spinning');
    }

    public function testProgressIsOnlyForWorkingAndBlocked(): void
    {
        [, $reporter] = $this->enabledOutput();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only supported for the "working" and "blocked" states, "done" given');

        $reporter->emit('done', 'Finished', null, 50);
    }

    public function testProgressIsBounded(): void
    {
        [, $reporter] = $this->enabledOutput();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be between 0 and 100, 101 given');

        $reporter->emit('working', 'Working', null, 101);
    }

    public function testNegativeProgressIsRejected(): void
    {
        [, $reporter] = $this->enabledOutput();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be between 0 and 100, -1 given');

        $reporter->emit('working', 'Working', null, -1);
    }

    public function testProgressBoundariesAreAccepted(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $reporter->emit('working', null, null, 0);
        $reporter->emit('working', null, null, 100);

        self::assertSame(
            "\x1b]7501;state=working:progress=0:app=queue\x1b\\"
            . "\x1b]7501;state=working:progress=100:app=queue\x1b\\",
            $read(),
        );
    }

    public function testKindIsOnlyForBlocked(): void
    {
        [, $reporter] = $this->enabledOutput();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('only supported for the "blocked" state, "working" given');

        $reporter->emit('working', 'Working', null, null, 'permission');
    }

    public function testInvalidKind(): void
    {
        [, $reporter] = $this->enabledOutput();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be one of: permission, question, auth');

        $reporter->emit('blocked', 'Waiting', null, null, 'wait');
    }

    public function testInvalidId(): void
    {
        [, $reporter] = $this->enabledOutput();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The terminal status id "bad id" must be up to 8 "/"-separated segments of 1 to 32 characters '
            . 'among letters, digits, "_", ".", "+" and "-", and at most 128 bytes.',
        );

        $reporter->emit('working', 'Working', 'bad id');
    }

    public function testLongIdIsRejected(): void
    {
        [, $reporter] = $this->enabledOutput();

        $this->expectException(InvalidArgumentException::class);

        $reporter->emit('working', 'Working', str_repeat('a', 45) . '/' . str_repeat('b', 45));
    }

    public function testManyIdSegmentsAreRejected(): void
    {
        [, $reporter] = $this->enabledOutput();

        $this->expectException(InvalidArgumentException::class);

        $reporter->emit(
            'working',
            'Working',
            implode('/', array_fill(0, 9, str_repeat('a', 14))),
        );
    }

    public function testIdAtTheLimitSizeIsAccepted(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $id = str_repeat('a', 32) . '/' . str_repeat('b', 32) . '/' . str_repeat('c', 32) . '/' . str_repeat('d', 29);
        $reporter->emit('working', null, $id);

        self::assertSame("\x1b]7501;state=working:id={$id}:app=queue\x1b\\", $read());
    }

    public function testInvalidApp(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be 1 to 32 characters');

        TerminalStatusReporter::forOutput($output, 'bad app', true);
    }

    public function testClearWithoutId(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $reporter->clear();

        self::assertSame("\x1b]7501;state=clear\x1b\\", $read());
    }

    public function testClearWithId(): void
    {
        [, $reporter, $read] = $this->enabledOutput();

        $reporter->clear('queue/a');

        self::assertSame("\x1b]7501;state=clear:id=queue/a\x1b\\", $read());
    }

    public function testClearWithInvalidId(): void
    {
        [, $reporter] = $this->enabledOutput();

        $this->expectException(InvalidArgumentException::class);

        $reporter->clear('bad id');
    }

    public function testNotEnabledForPlainOutput(): void
    {
        $reporter = TerminalStatusReporter::forOutput($this->createMock(OutputInterface::class));

        self::assertFalse($reporter->isEnabled());

        $reporter->emit('working', 'Working');
        $reporter->clear();
    }

    public function testNotEnabledForRedirectedStream(): void
    {
        $stream = fopen('php://memory', 'w+');
        $reporter = TerminalStatusReporter::forOutput(new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true));

        self::assertFalse($reporter->isEnabled());

        $reporter->emit('working', 'Working');
        self::assertSame('', $this->read($stream));
    }

    public function testNotEnabledForUndecoratedOutput(): void
    {
        $stream = fopen('php://memory', 'w+');
        $reporter = TerminalStatusReporter::forOutput(new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, false));

        self::assertFalse($reporter->isEnabled());

        $reporter->emit('working', 'Working');
        self::assertSame('', $this->read($stream));
    }

    public function testNotEnabledForQuietOutput(): void
    {
        $stream = fopen('php://memory', 'w+');
        $reporter = TerminalStatusReporter::forOutput(new StreamOutput($stream, OutputInterface::VERBOSITY_QUIET, true));

        self::assertFalse($reporter->isEnabled());

        $reporter->emit('working', 'Working');
        self::assertSame('', $this->read($stream));
    }

    public function testNotEnabledForBufferedOutput(): void
    {
        $reporter = TerminalStatusReporter::forOutput(new BufferedOutput());

        self::assertFalse($reporter->isEnabled());

        $reporter->emit('working', 'Working');
    }

    /**
     * @dataProvider provideOptOutValues
     */
    public function testOptOutViaEnvironment(string $value): void
    {
        putenv('YII_QUEUE_STATUS=' . $value);

        $stream = fopen('php://memory', 'w+');
        $reporter = TerminalStatusReporter::forOutput(new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true));

        self::assertFalse($reporter->isEnabled());

        $reporter->emit('working', 'Working');
        self::assertSame('', $this->read($stream));
    }

    public static function provideOptOutValues(): array
    {
        return [
            ['off'],
            ['OFF'],
            [' 0'],
            [' no '],
            ['false'],
            ['False'],
            [' false '],
        ];
    }

    public function testEnabledOverride(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);

        self::assertTrue($reporter->isEnabled());

        $reporter->emit('working', 'Working');
        self::assertNotSame('', $this->read($stream));
    }

    public function testDisabledOverrideWithStreamWritesNothing(): void
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, false);

        self::assertFalse($reporter->isEnabled());

        $reporter->emit('working', 'Working');
        $reporter->clear();

        self::assertSame('', $this->read($stream));
    }

    /**
     * @return array{0: StreamOutput, 1: TerminalStatusReporter, 2: callable(): string}
     */
    private function enabledOutput(): array
    {
        $stream = fopen('php://memory', 'w+');
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        $reporter = TerminalStatusReporter::forOutput($output, null, true);

        return [$output, $reporter, fn(): string => $this->read($stream)];
    }

    /** @param resource $stream */
    private function read($stream): string
    {
        rewind($stream);

        return (string) stream_get_contents($stream);
    }
}
