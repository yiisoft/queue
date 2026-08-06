<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueProducerInterface;
use Yiisoft\Queue\Worker\WorkerInterface;

/**
 * @internal
 */
final class SynchronousPushHandler implements PushHandlerInterface
{
    public function __construct(
        private readonly WorkerInterface $worker,
        private readonly QueueProducerInterface $queue,
    ) {}

    public function handlePush(MessageInterface $message): MessageInterface
    {
        $this->worker->process($message, $this->queue->getName(), $this->queue);

        return $message;
    }
}
