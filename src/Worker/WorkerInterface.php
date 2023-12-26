<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Worker;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;

interface WorkerInterface
{
    public function process(MessageInterface $message, QueueInterface $queue): MessageInterface;
}
