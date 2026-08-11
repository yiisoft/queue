<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Worker;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueProducerInterface;

interface WorkerInterface
{
    /** @param string $queue Logical execution queue. */
    public function process(
        MessageInterface $message,
        string $queue,
        ?QueueProducerInterface $retryProducer = null,
    ): MessageInterface;
}
