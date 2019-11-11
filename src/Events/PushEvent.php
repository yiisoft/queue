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
     * @var int
     */
    public $priority;

    /**
     * Creates BEFORE event.
     *
     * @param \Yiisoft\Yii\Queue\JobInterface $job
     * @param int $ttr
     * @param int $delay
     * @param int $priority
     * @return self created event
     */
    public static function before(JobInterface $job, int $ttr, int $delay, int $priority): self
    {
        $event = new static(self::BEFORE, null, $job, $ttr);
        $event->delay = $delay;
        $event->priority = $priority;

        return $event;
    }

    /**
     * Creates AFTER event.
     *
     * @param PushEvent $before
     * @return self created event
     */
    public static function after(self $before): self
    {
        $event = new static(self::AFTER, $before->id, $before->job, $before->ttr);
        $event->delay = $before->delay;
        $event->priority = $before->priority;

        return $event;
    }
}
