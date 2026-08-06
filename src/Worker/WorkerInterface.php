<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Worker;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueProducerInterface;

interface WorkerInterface
{
    /** @param string $queueName Logical execution queue name. */
    public function process(
        MessageInterface $message,
        string $queueName,
        ?QueueProducerInterface $retryProducer = null,
    ): MessageInterface;
}
