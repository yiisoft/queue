<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Processors;

use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Queue;

interface JobProcessorInterface
{
    public function process(MessageInterface $message, Queue $queue);
}
