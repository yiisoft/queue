<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Cli;

use yii\base\BaseObject;

/**
 * Signal Loop.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 *
 * @since 2.0.2
 */
class SignalLoop extends BaseObject implements LoopInterface
{
    /**
     * @var array of signals to exit from listening of the queue.
     */
    public $exitSignals = [
        15, // SIGTERM
        2,  // SIGINT
        1,  // SIGHUP
    ];
    /**
     * @var array of signals to suspend listening of the queue.
     *            For example: SIGTSTP
     */
    public $suspendSignals = [];
    /**
     * @var array of signals to resume listening of the queue.
     *            For example: SIGCONT
     */
    public $resumeSignals = [];

    /**
     * @var Queue
     */
    protected $queue;

    /**
     * @var bool status when exit signal was got.
     */
    private static $exit = false;
    /**
     * @var bool status when suspend or resume signal was got.
     */
    private static $pause = false;

    /**
     * @param Queue $queue
     *                     {@inheritdoc}
     */
    public function __construct($queue)
    {
        $this->queue = $queue;
        if (extension_loaded('pcntl')) {
            foreach ($this->exitSignals as $signal) {
                pcntl_signal($signal, function () {
                    self::$exit = true;
                });
            }
            foreach ($this->suspendSignals as $signal) {
                pcntl_signal($signal, function () {
                    self::$pause = true;
                });
            }
            foreach ($this->resumeSignals as $signal) {
                pcntl_signal($signal, function () {
                    self::$pause = false;
                });
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
            while (self::$pause && !self::$exit) {
                usleep(10000);
                pcntl_signal_dispatch();
            }
        }

        return !self::$exit;
    }
}
