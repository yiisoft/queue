<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Worker;

use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Queue;

interface WorkerInterface
{
    public function process(MessageInterface $message, Queue $queue);
}
