<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue;

/**
 * Push Event.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class PushEvent extends JobEvent
{
    /**
     * @event PushEvent
     */
    const BEFORE = 'before.push';
    /**
     * @event PushEvent
     */
    const AFTER = 'after.push';

    /**
     * @var int
     */
    public $delay;
    /**
     * @var mixed
     */
    public $priority;

    /**
     * Creates BEFORE event.
     *
     * @return self created event
     */
    public static function before($job, $ttr, $delay, $priority): self
    {
        $event = new static(static::BEFORE, null, $job, $ttr);
        $event->delay = $delay;
        $event->priority = $priority;

        return $event;
    }

    /**
     * Creates AFTER event.
     *
     * @return self created event
     */
    public static function after(self $before): self
    {
        $event = new static(static::AFTER, $before->id, $before->job, $before->ttr);
        $event->delay = $before->delay;
        $event->priority = $before->priority;

        return $event;
    }
}
