<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Events;

use Yiisoft\Yii\Queue\Queue;

interface EventInterface
{
    public function getQueue(): Queue;
}
