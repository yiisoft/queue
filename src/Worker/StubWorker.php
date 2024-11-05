<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Worker;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;

final class StubWorker implements WorkerInterface
{
    public function process(MessageInterface $message, QueueInterface $queue): MessageInterface
    {
        return $message;
    }
}
