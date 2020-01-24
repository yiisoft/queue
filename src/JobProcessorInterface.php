<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

interface JobProcessorInterface
{
    public function process(MessageInterface $message, Queue $queue);
}
