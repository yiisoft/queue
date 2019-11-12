<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Events;

use Yiisoft\Yii\Queue\JobInterface;

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
    public const BEFORE = 'before.exec';
    /**
     * @event ExecEvent
     */
    public const AFTER = 'after.exec';
    /**
     * @event ExecEvent
     */
    public const ERROR = 'error.exec';

    /**
     * @var int attempt number.
     *
     * @see ExecEvent::BEFORE
     * @see ExecEvent::AFTER
     * @see ErrorEvent::AFTER
     */
    public $attempt;
    /**
     * @var mixed result of a job execution in case job is done.
     *
     * @see ExecEvent::AFTER
     * @since 2.1.1
     */
    public $result;
    /**
     * @var \Throwable|null
     *
     * @see ErrorEvent::AFTER
     * @since 2.1.1
     */
    public $error;
    /**
     * @var null|bool
     *
     * @see ErrorEvent::AFTER
     * @since 2.1.1
     */
    public $retry;

    /**
     * Creates BEFORE event.
     * @param $id
     * @param \Yiisoft\Yii\Queue\JobInterface $job
     * @param int $ttr
     * @param int $attempt
     * @param \Throwable|null $error
     * @return self created event
     */
    public static function before($id, JobInterface $job, int $ttr, int $attempt, ?\Throwable $error): self
    {
        $event = new static(static::BEFORE, $id, $job, $ttr);
        $event->attempt = $attempt;
        $event->error = $error;

        return $event;
    }

    /**
     * Creates AFTER event.
     *
     * @param \Yiisoft\Yii\Queue\Events\ExecEvent $before
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

    /**
     * Creates BEFORE event.
     *
     * @param \Yiisoft\Yii\Queue\Events\ExecEvent $before
     * @return self created event
     */
    public static function error(self $before): self
    {
        $event = new static(static::ERROR, $before->id, $before->job, $before->ttr);
        $event->attempt = $before->attempt;
        $event->result = $before->result;
        $event->error = $before->error;
        $event->retry = $before->retry;

        return $event;
    }
}
