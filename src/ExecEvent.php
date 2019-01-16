<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue;

/**
 * Exec Event.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class ExecEvent extends JobEvent
{
    /**
     * @event ExecEvent
     */
    const BEFORE = 'before.exec';
    /**
     * @event ExecEvent
     */
    const AFTER = 'after.exec';

    /**
     * @var int attempt number.
     * @see Queue::EVENT_BEFORE_EXEC
     * @see Queue::EVENT_AFTER_EXEC
     * @see Queue::EVENT_AFTER_ERROR
     */
    public $attempt;
    /**
     * @var mixed result of a job execution in case job is done.
     * @see Queue::EVENT_AFTER_EXEC
     * @since 2.1.1
     */
    public $result;
    /**
     * @var null|\Exception|\Throwable
     * @see Queue::EVENT_AFTER_ERROR
     * @since 2.1.1
     */
    public $error;
    /**
     * @var null|bool
     * @see Queue::EVENT_AFTER_ERROR
     * @since 2.1.1
     */
    public $retry;

    /**
     * Creates BEFORE event.
     * @return self created event
     */
    public static function before($id, $job, $ttr, $attempt, $error): self
    {
        $event = new static(static::BEFORE, $id, $job, $ttr);
        $event->attempt = $attempt;
        $event->error = $error;
        return $event;
    }

    /**
     * Creates AFTER event.
     * @return self created event
     */
    public static function after(self $before): self
    {
        $event = new static(static::AFTER, $before->id, $before->job, $before->ttr);
        $event->attempt = $before->attempt;
        $event->result = $before->result;
        $event->error = $before->error;
        $event->retry = $before->retry;
        return $event;
    }
}
