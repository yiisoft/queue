<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue\cli;

use yii\base\Event;

/**
 * Worker Event.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 *
 * @since 2.0.2
 */
class WorkerEvent extends Event
{
    /**
     * @event WorkerEvent that is triggered when the worker is started.
     *
     * @since 2.0.2
     */
    const START = 'worker.start';
    /**
     * @event WorkerEvent that is triggered each iteration between requests to queue.
     *
     * @since 2.0.3
     */
    const LOOP = 'worker.loop';
    /**
     * @event WorkerEvent that is triggered when the worker is stopped.
     *
     * @since 2.0.2
     */
    const STOP = 'worker.stop';

    /**
     * @var Queue
     *            {@inheritdoc}
     */
    public $sender;
    /**
     * @var LoopInterface
     */
    public $loop;
    /**
     * @var null|int exit code
     */
    public $exitCode;

    public function __construct(string $name, $loop, $exitCode)
    {
        parent::__construct($name);
        $this->loop = $loop;
        $this->exitCode = $exitCode;
    }

    /**
     * Creates START event.
     *
     * @return self created event
     */
    public static function start($loop): self
    {
        return new static(static::START, $loop, null);
    }

    /**
     * Creates LOOP event.
     *
     * @return self created event
     */
    public static function loop(self $before): self
    {
        return new static(static::LOOP, $before->loop, $before->exitCode);
    }

    /**
     * Creates STOP event.
     *
     * @return self created event
     */
    public static function stop(self $before): self
    {
        return new static(static::STOP, $before->loop, $before->exitCode);
    }
}
