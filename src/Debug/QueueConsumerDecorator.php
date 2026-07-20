<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueConsumerInterface;
use Yiisoft\Queue\QueueInterface;

final class QueueConsumerDecorator implements QueueInterface, QueueConsumerInterface
{
    private readonly QueueDecorator $queueDecorator;

    public function __construct(
        private readonly QueueInterface&QueueConsumerInterface $queue,
        QueueCollector $collector,
    ) {
        $this->queueDecorator = new QueueDecorator($queue, $collector);
    }

    public function status(string|int $id): MessageStatus
    {
        return $this->queueDecorator->status($id);
    }

    public function push(MessageInterface $message): MessageInterface
    {
        return $this->queueDecorator->push($message);
    }

    public function run(int $max = 0): int
    {
        return $this->queue->run($max);
    }

    public function listen(): void
    {
        $this->queue->listen();
    }

    public function getName(): string
    {
        return $this->queueDecorator->getName();
    }
}
