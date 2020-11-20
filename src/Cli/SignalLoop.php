<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Cli;

use Psr\EventDispatcher\EventDispatcherInterface;

class SignalLoop implements LoopInterface
{
    use SoftLimitTrait;

    protected const SIGNALS_EXIT = [SIGHUP, SIGINT, SIGTERM];
    protected const SIGNALS_SUSPEND = [SIGTSTP];
    protected const SIGNALS_RESUME = [SIGCONT];

    protected int $memorySoftLimit;
    protected EventDispatcherInterface $dispatcher;
    protected bool $pause;
    protected bool $exit;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param int $memorySoftLimit Soft RAM limit in bytes. The loop won't let you continue to execute the program if
     *     soft limit is reached. Zero means no limit.
     */
    public function __construct(EventDispatcherInterface $dispatcher, int $memorySoftLimit = 0)
    {
        $this->dispatcher = $dispatcher;
        $this->memorySoftLimit = $memorySoftLimit;

        foreach (self::SIGNALS_EXIT as $signal) {
            pcntl_signal($signal, fn () => $this->exit = true);
        }
        foreach (self::SIGNALS_SUSPEND as $signal) {
            pcntl_signal($signal, fn () => $this->pause = true);
        }
        foreach (self::SIGNALS_RESUME as $signal) {
            pcntl_signal($signal, fn () => $this->pause = false);
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
        $this->pause = false;
        $this->exit = false;

        pcntl_signal_dispatch();

        // Wait for resume signal until loop is suspended
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

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->dispatcher;
    }
}
