<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Events;

use Yiisoft\Yii\Queue\Jobs\JobInterface;

interface BeforePushInterface extends EventInterface, StoppableEventInterface
{
    public function getJob(): JobInterface;
}
