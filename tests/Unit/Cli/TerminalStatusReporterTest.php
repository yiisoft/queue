<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Yiisoft\Queue\Cli\TerminalStatusReporter;

final class TerminalStatusReporterTest extends TestCase
{
    /** @var array<string, string|false> */
    private array $environment = [];

    protected function setUp(): void
    {
        foreach (['YII_TERMINAL_STATUS'] as $name) {
            $this->environment[$name] = getenv($name);
            putenv($name);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->environment as $name => $value) {
            putenv($value === false ? $name : $name . '=' . $value);
        }
    }

    public function testForcedDecorationAndEnvironmentOnDoNotEnableRedirectedOutput(): void
    {
        putenv('YII_TERMINAL_STATUS=on');
        $stream = fopen('php://memory', 'w+');
        $writer = TerminalStatusReporter::forOutput(new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true));
        $writer->emit('working', 'Processing');

        self::assertFalse($writer->isEnabled());
        rewind($stream);
        self::assertSame('', stream_get_contents($stream));
    }

    public function testCustomBufferedOutputIsDisabled(): void
    {
        self::assertFalse(TerminalStatusReporter::forOutput(new BufferedOutput(decorated: true))->isEnabled());
    }

    public function testActualTerminalStderrIsSelectedAndInvocationsHaveDifferentRecords(): void
    {
        $stream = $this->terminal();
        $output = new ConsoleOutput();
        $output->setErrorOutput(new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true));
        $first = TerminalStatusReporter::forOutput($output);
        $second = TerminalStatusReporter::forOutput($output);

        self::assertTrue($first->isEnabled());
        self::assertTrue($second->isEnabled());
        // The master PTY provides a real TTY; encoding and per-invocation isolation are also covered by writer tests.
        self::assertNotEquals($first, $second);
        fclose($stream);
    }

    public function testRedirectedStderrIsNotReplacedWithTerminalStdout(): void
    {
        $output = new ConsoleOutput(decorated: true);
        $output->setErrorOutput(new StreamOutput(fopen('php://memory', 'w+'), OutputInterface::VERBOSITY_NORMAL, true));
        self::assertFalse(TerminalStatusReporter::forOutput($output)->isEnabled());
    }

    public function testQuietAndUndecoratedTerminalsAreDisabled(): void
    {
        $stream = $this->terminal();
        self::assertFalse(TerminalStatusReporter::forOutput(new StreamOutput($stream, OutputInterface::VERBOSITY_QUIET, true))->isEnabled());
        self::assertFalse(TerminalStatusReporter::forOutput(new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, false))->isEnabled());
        fclose($stream);
    }

    public function testFamilyEnvironmentDisablesTerminalOutput(): void
    {
        $stream = $this->terminal();
        $output = new StreamOutput($stream, OutputInterface::VERBOSITY_NORMAL, true);
        foreach (['off', '0', 'no', ' FALSE '] as $value) {
            putenv('YII_TERMINAL_STATUS=' . $value);
            self::assertFalse(TerminalStatusReporter::forOutput($output)->isEnabled());
        }
        fclose($stream);
    }

    /** @return resource */
    private function terminal()
    {
        if (!file_exists('/dev/ptmx')) {
            self::markTestSkipped('A Unix PTY is required for positive TTY checks.');
        }
        $stream = fopen('/dev/ptmx', 'r+');
        if (!stream_isatty($stream)) {
            fclose($stream);
            self::markTestSkipped('The PTY is not exposed as a TTY.');
        }
        return $stream;
    }
}
