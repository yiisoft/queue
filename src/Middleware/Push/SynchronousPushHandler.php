<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Worker\WorkerInterface;

/**
 * @internal
 */
final class SynchronousPushHandler implements MessageHandlerPushInterface
{
    public function __construct(
        private readonly WorkerInterface $worker,
        private readonly QueueInterface $queue,
    ) {}

    public function handlePush(MessageInterface $message): MessageInterface
    {
        $this->worker->process($message, $this->queue);

        return $message;
    }
}
