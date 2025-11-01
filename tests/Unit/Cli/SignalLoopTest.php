<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Cli;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Queue\Cli\SignalLoop;

final class SignalLoopTest extends TestCase
{
    public function testMemoryLimitReached(): void
    {
        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped('This rest requires PCNTL extension');
        }

        $loop = new SignalLoop(1);
        self::assertFalse($loop->canContinue());
    }

    public function testSuspendAndResume(): void
    {
        if (!function_exists('pcntl_signal')) {
            $this->markTestSkipped('pcntl not available');
        }

        pcntl_async_signals(true);

        $loop = new SignalLoop(0);
        pcntl_signal(SIGALRM, static function (): void {
            posix_kill(getmypid(), SIGCONT);
        });

        posix_kill(getmypid(), SIGTSTP);
        pcntl_alarm(1);

        $start = microtime(true);
        $result = $loop->canContinue();
        $elapsed = microtime(true) - $start;

        self::assertTrue($result);
        self::assertGreaterThan(0.5, $elapsed);
    }

    #[DataProvider('exitSignalProvider')]
    public function testExitSignals(int $signal): void
    {
        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped('PCNTL extension not available');
        }
        pcntl_async_signals(true);

        $loop = new SignalLoop(0);

        self::assertTrue($loop->canContinue(), 'Loop should continue');
        posix_kill(getmypid(), $signal);

        self::assertFalse($loop->canContinue(), "Loop should not continue after receiving signal {$signal}");
    }

    public static function exitSignalProvider(): iterable
    {
        yield 'SIGHUP' => [SIGHUP];
        yield 'SIGINT' => [SIGINT];
        yield 'SIGTERM' => [SIGTERM];
    }

    public function testResumeSignal(): void
    {
        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped('PCNTL extension not available');
        }
        pcntl_async_signals(true);

        $loop = new SignalLoop(0);

        // First suspend the loop
        posix_kill(getmypid(), SIGTSTP);

        // Then immediately resume
        posix_kill(getmypid(), SIGCONT);

        $start = microtime(true);
        $result = $loop->canContinue();
        $elapsed = microtime(true) - $start;

        self::assertTrue($result);
        self::assertLessThan(0.1, $elapsed, 'Loop should resume quickly without waiting');
    }

    public function testMultipleExitSignals(): void
    {
        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped('PCNTL extension not available');
        }
        pcntl_async_signals(true);

        $loop = new SignalLoop(0);

        // Send multiple exit signals
        posix_kill(getmypid(), SIGINT);
        posix_kill(getmypid(), SIGTERM);

        $result = $loop->canContinue();

        self::assertFalse($result, 'Loop should not continue after receiving any exit signal');
    }

    public function testSuspendOverridesResume(): void
    {
        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped('PCNTL extension not available');
        }
        pcntl_async_signals(true);

        $loop = new SignalLoop(0);

        // Resume first
        posix_kill(getmypid(), SIGCONT);
        // Then suspend
        posix_kill(getmypid(), SIGTSTP);

        // Set up alarm to resume after 1 second
        pcntl_signal(SIGALRM, static function (): void {
            posix_kill(getmypid(), SIGCONT);
        });
        pcntl_alarm(1);

        $start = microtime(true);
        $result = $loop->canContinue();
        $elapsed = microtime(true) - $start;

        self::assertTrue($result);
        self::assertGreaterThan(0.5, $elapsed, 'Loop should wait for resume after suspend');
    }
}
