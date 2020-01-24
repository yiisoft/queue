<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue\Events;

use Throwable;
use Yiisoft\Yii\Queue\JobInterface;
use Yiisoft\Yii\Queue\Queue;

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
    public ?int $attempt = null;
    /**
     * @var mixed result of a job execution in case job is done.
     *
     * @see ExecEvent::AFTER
     * @since 2.1.1
     */
    public $result;
    /**
     * @var Throwable|null
     *
     * @see ErrorEvent::AFTER
     * @since 2.1.1
     */
    public ?Throwable $error;
    /**
     * @var null|bool
     *
     * @see ErrorEvent::AFTER
     * @since 2.1.1
     */
    public ?bool $retry = null;
    protected ?Queue $queue = null;

    /**
     * Creates BEFORE event.
     *
     * @param $id
     * @param JobInterface|null $job
     * @param int $ttr
     * @param int $attempt
     *
     * @return self created event
     */
    public static function before($id, ?JobInterface $job, int $ttr, int $attempt): self
    {
        $event = new static(static::BEFORE, $id, $job, $ttr);
        $event->attempt = $attempt;

        return $event;
    }

    /**
     * Creates AFTER event.
     *
     * @param ExecEvent $before
     *
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
     * @param Queue $queue
     * @param ExecEvent $before
     *
     * @param Throwable $error
     *
     * @return self created event
     */
    public static function error(Queue $queue, self $before, Throwable $error): self
    {
        $event = new static(static::ERROR, $before->id, $before->job, $before->ttr);
        $event->queue = $queue;
        $event->attempt = $before->attempt;
        $event->result = $before->result;
        $event->error = $error;
        $event->retry = $before->retry;

        return $event;
    }

    public function getQueue(): ?Queue
    {
        return $this->queue;
    }
}
