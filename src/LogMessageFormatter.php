<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Yiisoft\Yii\Queue\Events\ExecEvent;
use Yiisoft\Yii\Queue\Events\JobEvent;

class LogMessageFormatter
{
    public function getJobTitle(JobEvent $event): string
    {
        if ($event->job === null) {
            $name = get_class($event->job);
        } else {
            $name = 'unknown job';
        }

        return "[$event->id] $name";
    }

    public function getExecTitle(ExecEvent $event): string
    {
        $title = $this->getJobTitle($event);
        $extra = "attempt: $event->attempt";
        if ($pid = getmypid()) {
            $extra .= ", PID: $pid";
        }

        return "$title ($extra)";
    }
}
