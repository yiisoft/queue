<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Cli;

use Yiisoft\Yii\Queue\Queue;

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
    protected array $exitSignals = [
        15, // SIGTERM
        2,  // SIGINT
        1,  // SIGHUP
    ];
    /**
     * @var array of signals to suspend listening of the queue.
     *            For example: SIGTSTP
     */
    protected array $suspendSignals = [];
    /**
     * @var array of signals to resume listening of the queue.
     *            For example: SIGCONT
     */
    protected array $resumeSignals = [];

    /**
     * @var Queue
     */
    protected Queue $queue;

    /**
     * @var bool status when exit signal was got.
     */
    protected bool $exit = false;
    /**
     * @var bool status when suspend or resume signal was got.
     */
    protected bool $pause = false;

    /**
     * @param Queue $queue
     */
    public function __construct($queue)
    {
        $this->queue = $queue;
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
