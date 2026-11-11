<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use Yiisoft\Queue\Cli\TerminalStatusWriter;

use function is_resource;

final class TerminalStatusWriterTest extends TestCase
{
    public function testIndependentInvocationsDoNotShareRootOrChildRecords(): void
    {
        $stream = fopen('php://memory', 'w+');
        $first = new TerminalStatusWriter($stream, true, 'queue/first');
        $second = new TerminalStatusWriter($stream, true, 'queue/second');
        $first->emit('working', 'First', 'queue-1');
        $second->emit('working', 'Second', 'queue-1');
        $first->clear();
        rewind($stream);
        $bytes = stream_get_contents($stream);
        self::assertStringContainsString('id=queue/first/queue-1:', $bytes);
        self::assertStringContainsString('id=queue/second/queue-1:', $bytes);
        self::assertStringContainsString("state=clear:id=queue/first\x1b\\", $bytes);
        self::assertStringNotContainsString("state=clear\x1b\\", $bytes);
        self::assertTrue(is_resource($stream));
    }

    public function testClosedStreamDoesNotInterruptWork(): void
    {
        $stream = fopen('php://memory', 'w+');
        $writer = new TerminalStatusWriter($stream, true, 'queue/run');
        fclose($stream);
        $writer->emit('working', 'Processing');
        self::assertFalse($writer->isEnabled());
    }

    public function testWriteFailureDisablesReporting(): void
    {
        $stream = fopen(__FILE__, 'r');
        $writer = new TerminalStatusWriter($stream, true, 'queue/run');
        $writer->emit('working', 'Processing');
        self::assertFalse($writer->isEnabled());
        fclose($stream);
    }

    public function testDisabledWriterDoesNotWrite(): void
    {
        $stream = fopen('php://memory', 'w+');
        $writer = new TerminalStatusWriter($stream, false, 'queue/run');
        $writer->emit('working', 'Processing');
        $writer->clear();
        rewind($stream);
        self::assertSame('', stream_get_contents($stream));
    }
}
