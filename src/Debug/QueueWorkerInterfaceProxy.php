<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueProducerInterface;
use Yiisoft\Queue\Worker\WorkerInterface;

/**
 * Debug proxy for {@see WorkerInterface} that records processed messages into the {@see QueueCollector}.
 */
final class QueueWorkerInterfaceProxy implements WorkerInterface
{
    public function __construct(
        private readonly WorkerInterface $worker,
        private readonly QueueCollector $collector,
    ) {}

    public function process(
        MessageInterface $message,
        string $queueName,
        ?QueueProducerInterface $retryProducer = null,
    ): void {
        $this->collector->collectWorkerProcessing($message, $queueName);
        $this->worker->process($message, $queueName, $retryProducer);
    }
}
