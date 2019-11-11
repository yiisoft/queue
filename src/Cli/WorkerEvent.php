<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Cli;

/**
 * Worker Event.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 *
 * @since 2.0.2
 */
class WorkerEvent
{
    /**
     * @event WorkerEvent that is triggered when the worker is started.
     *
     * @since 2.0.2
     */
    public const START = 'worker.start';
    /**
     * @event WorkerEvent that is triggered each iteration between requests to queue.
     *
     * @since 2.0.3
     */
    public const LOOP = 'worker.loop';
    /**
     * @event WorkerEvent that is triggered when the worker is stopped.
     *
     * @since 2.0.2
     */
    public const STOP = 'worker.stop';

    /**
     * @var Queue
     */
    public $sender;
    /**
     * @var string
     */
    public $name;
    /**
     * @var LoopInterface
     */
    public $loop;
    /**
     * @var int exit code
     */
    public $exitCode;

    public function __construct(string $name, LoopInterface $loop, int $exitCode)
    {
        $this->name = $name;
        $this->loop = $loop;
        $this->exitCode = $exitCode;
    }

    /**
     * Creates START event.
     *
     * @param \Yiisoft\Yii\Queue\Cli\LoopInterface $loop
     * @return self created event
     */
    public static function start(LoopInterface $loop): self
    {
        return new static(static::START, $loop, null);
    }

    /**
     * Creates LOOP event.
     *
     * @param \Yiisoft\Yii\Queue\Cli\WorkerEvent $before
     * @return self created event
     */
    public static function loop(self $before): self
    {
        return new static(static::LOOP, $before->loop, $before->exitCode);
    }

    /**
     * Creates STOP event.
     *
     * @param \Yiisoft\Yii\Queue\Cli\WorkerEvent $before
     * @return self created event
     */
    public static function stop(self $before): self
    {
        return new static(static::STOP, $before->loop, $before->exitCode);
    }
}
