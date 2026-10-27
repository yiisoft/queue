<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Cli;

use const SIGCONT;
use const SIGHUP;
use const SIGINT;
use const SIGTERM;
use const SIGTSTP;

/**
 * A loop that stops on `SIGHUP`, `SIGINT` and `SIGTERM`, and pauses on `SIGTSTP` until `SIGCONT`.
 *
 * Signal handlers are installed by {@see start()} and the previous ones are restored by {@see finish()}, so creating
 * the loop doesn't affect handlers registered by other code, such as Symfony Console. If {@see canContinue()} is
 * called outside of {@see start()} and {@see finish()}, the handlers are installed then and kept.
 */
final class SignalLoop implements ScopedLoopInterface
{
    use SoftLimitTrait;

    protected const SIGNALS_EXIT = [SIGHUP, SIGINT, SIGTERM];
    protected const SIGNALS_SUSPEND = [SIGTSTP];
    protected const SIGNALS_RESUME = [SIGCONT];
    protected bool $pause = false;
    protected bool $exit = false;

    /**
     * @var array<int, mixed>|null Handlers that were active before ours were installed, by signal. `null` if our
     * handlers are not installed.
     */
    private ?array $previousHandlers = null;
    private int $depth = 0;
    private bool $installedByStart = false;

    /**
     * @param int $memorySoftLimit Soft RAM limit in bytes. The loop won't let you continue to execute the program if
     * soft limit is reached. Zero means no limit.
     */
    public function __construct(protected int $memorySoftLimit = 0) {}

    public function start(): void
    {
        if ($this->depth++ === 0 && $this->previousHandlers === null) {
            $this->installHandlers();
            $this->installedByStart = true;
        }
    }

    public function finish(): void
    {
        if ($this->depth === 0) {
            return;
        }

        if (--$this->depth === 0 && $this->installedByStart) {
            $this->restoreHandlers();
        }
    }

    /**
     * Checks signals state.
     *
     * {@inheritdoc}
     */
    public function canContinue(): bool
    {
        if ($this->memoryLimitReached()) {
            return false;
        }

        return $this->dispatchSignals();
    }

    protected function dispatchSignals(): bool
    {
        if ($this->previousHandlers === null) {
            $this->installHandlers();
        }

        pcntl_signal_dispatch();

        // Wait for resume signal until the loop is suspended
        while ($this->pause && !$this->exit) {
            usleep(10000);
            pcntl_signal_dispatch();
        }

        return !$this->exit;
    }

    protected function getMemoryLimit(): int
    {
        return $this->memorySoftLimit;
    }

    private function installHandlers(): void
    {
        $handlers = [];
        foreach (self::SIGNALS_EXIT as $signal) {
            $handlers[$signal] = function (): void {
                $this->exit = true;
            };
        }
        foreach (self::SIGNALS_SUSPEND as $signal) {
            $handlers[$signal] = function (): void {
                $this->pause = true;
            };
        }
        foreach (self::SIGNALS_RESUME as $signal) {
            $handlers[$signal] = function (): void {
                $this->pause = false;
            };
        }

        $this->previousHandlers = [];
        foreach ($handlers as $signal => $handler) {
            $this->previousHandlers[$signal] = pcntl_signal_get_handler($signal);
            pcntl_signal($signal, $handler);
        }
    }

    private function restoreHandlers(): void
    {
        // Deliver signals received so far to our handlers, so that they are not lost.
        pcntl_signal_dispatch();

        foreach ($this->previousHandlers ?? [] as $signal => $handler) {
            /** @psalm-var callable():void|int $handler */
            pcntl_signal($signal, $handler);
        }

        $this->previousHandlers = null;
        $this->installedByStart = false;
    }
}
