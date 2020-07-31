<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Cli;

use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Yii\Queue\Event\MemoryLimitReached;

/**
 * Signal Loop.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 *
 * @since 2.0.2
 */
class SignalLoop implements LoopInterface
{
    /**
     * @var array of signals to exit from listening of the queue.
     */
    protected array $exitSignals;
    /**
     * @var array of signals to suspend listening of the queue.
     *            For example: SIGTSTP
     */
    protected array $suspendSignals;
    /**
     * @var array of signals to resume listening of the queue.
     *            For example: SIGCONT
     */
    protected array $resumeSignals;

    /**
     * @var bool status when exit signal was got.
     */
    protected bool $exit = false;
    /**
     * @var bool status when suspend or resume signal was got.
     */
    protected bool $pause = false;
    private int $memorySoftLimit;
    private EventDispatcherInterface $dispatcher;

    /**
     * @param EventDispatcherInterface $dispatcher
     * @param int $memorySoftLimit Soft RAM limit in bytes. The loop won't let you continue to execute the program if soft limit is reached. Zero means no limit.
     * @param int[] $exitSignals pcntl signal codes to exit the loop
     * @param int[] $suspendSignals pcntl signal codes to pause loop execution
     * @param int[] $resumeSignals pcntl signal codes to resume loop execution
     */
    public function __construct(
        EventDispatcherInterface $dispatcher,
        $memorySoftLimit = 0,
        $exitSignals = [1, 2, 15], // SIGHUP, SIGINT, SIGTERM
        $suspendSignals = [17, 20, 23, 24], // partly SIGSTOP, SIGTSTP
        $resumeSignals = [25] // partly SIGCONT
    )
    {
        $this->dispatcher = $dispatcher;
        $this->memorySoftLimit = $memorySoftLimit;
        $this->exitSignals = $exitSignals;
        $this->suspendSignals = $suspendSignals;
        $this->resumeSignals = $resumeSignals;

        if (extension_loaded('pcntl')) {
            foreach ($this->exitSignals as $signal) {
                pcntl_signal($signal, fn () => $this->exit = true);
            }
            foreach ($this->suspendSignals as $signal) {
                pcntl_signal($signal, fn () => $this->pause = true);
            }
            foreach ($this->resumeSignals as $signal) {
                pcntl_signal($signal, fn () => $this->pause = false);
            }
        }
    }

    /**
     * Checks signals state.
     *
     * {@inheritdoc}
     */
    public function canContinue(): bool
    {
        $memoryUsage = memory_get_usage(true);
        if ($this->memorySoftLimit !== 0 && $memoryUsage >= $this->memorySoftLimit) {
            $this->exit = true;
            $this->dispatcher->dispatch(new MemoryLimitReached($this->memorySoftLimit, $memoryUsage));
        }

        if (extension_loaded('pcntl')) {
            pcntl_signal_dispatch();
            // Wait for resume signal until loop is suspended
            while ($this->pause && !$this->exit) {
                usleep(10000);
                pcntl_signal_dispatch();
            }
        }

        return !$this->exit;
    }

    public function setResumeSignals(array $resumeSignals): void
    {
        $this->resumeSignals = $resumeSignals;
    }

    public function setSuspendSignals(array $suspendSignals): void
    {
        $this->suspendSignals = $suspendSignals;
    }
}
