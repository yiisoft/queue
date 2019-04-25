<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue;

use yii\base\Behavior;
use yii\helpers\Yii;
use Yiisoft\Yii\Queue\Events\ErrorEvent;
use Yiisoft\Yii\Queue\Events\ExecEvent;
use Yiisoft\Yii\Queue\Events\JobEvent;
use Yiisoft\Yii\Queue\Events\PushEvent;

/**
 * Log Behavior.
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
class LogBehavior extends Behavior
{
    /**
     * @var Queue
     *            {@inheritdoc}
     */
    public $owner;
    /**
     * @var bool
     */
    public $autoFlush = true;

    /**
     * {@inheritdoc}
     */
    public function events()
    {
        return [
            PushEvent::AFTER       => 'afterPush',
            ExecEvent::BEFORE      => 'beforeExec',
            ExecEvent::AFTER       => 'afterExec',
            ErrorEvent::AFTER      => 'afterError',
            Cli\WorkerEvent::START => 'workerStart',
            Cli\WorkerEvent::STOP  => 'workerStop',
        ];
    }

    /**
     * @param PushEvent $event
     */
    public function afterPush(PushEvent $event)
    {
        $title = $this->getJobTitle($event);
        Yii::info("$title is pushed.", Queue::class);
    }

    /**
     * @param ExecEvent $event
     */
    public function beforeExec(ExecEvent $event)
    {
        $title = $this->getExecTitle($event);
        Yii::info("$title is started.", Queue::class);
        Yii::beginProfile($title, Queue::class);
    }

    /**
     * @param ExecEvent $event
     */
    public function afterExec(ExecEvent $event)
    {
        $title = $this->getExecTitle($event);
        Yii::endProfile($title, Queue::class);
        Yii::info("$title is finished.", Queue::class);
        if ($this->autoFlush) {
            Yii::get('logger')->flush(true);
        }
    }

    /**
     * @param ExecEvent $event
     */
    public function afterError(ExecEvent $event)
    {
        $title = $this->getExecTitle($event);
        Yii::endProfile($title, Queue::class);
        Yii::error("$title is finished with error: $event->error.", Queue::class);
        if ($this->autoFlush) {
            Yii::get('logger')->flush(true);
        }
    }

    /**
     * @param cli\WorkerEvent $event
     *
     * @since 2.0.2
     */
    public function workerStart(cli\WorkerEvent $event)
    {
        $title = 'Worker '.$event->getTarget()->getWorkerPid();
        Yii::info("$title is started.", Queue::class);
        Yii::beginProfile($title, Queue::class);
        if ($this->autoFlush) {
            Yii::get('logger')->flush(true);
        }
    }

    /**
     * @param cli\WorkerEvent $event
     *
     * @since 2.0.2
     */
    public function workerStop(cli\WorkerEvent $event)
    {
        $title = 'Worker '.$event->getTarget()->getWorkerPid();
        Yii::endProfile($title, Queue::class);
        Yii::info("$title is stopped.", Queue::class);
        if ($this->autoFlush) {
            Yii::get('logger')->flush(true);
        }
    }

    /**
     * @param JobEvent $event
     *
     * @return string
     *
     * @since 2.0.2
     */
    protected function getJobTitle(JobEvent $event)
    {
        $name = $event->job instanceof JobInterface ? get_class($event->job) : 'unknown job';

        return "[$event->id] $name";
    }

    /**
     * @param ExecEvent $event
     *
     * @return string
     *
     * @since 2.0.2
     */
    protected function getExecTitle(ExecEvent $event)
    {
        $title = $this->getJobTitle($event);
        $extra = "attempt: $event->attempt";
        if ($pid = $event->getTarget()->getWorkerPid()) {
            $extra .= ", PID: $pid";
        }

        return "$title ($extra)";
    }
}
