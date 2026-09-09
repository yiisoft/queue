<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Worker\Worker;
use Yiisoft\Queue\SyncQueueProducer;
use Yiisoft\Queue\Message\MessageInterface;

/**
 * @internal
 */
final class SynchronousPushHandler implements PushHandlerInterface
{
    public function __construct(
        private readonly Worker $worker,
        private readonly SyncQueueProducer $queue,
    ) {}

    public function handlePush(PushRequest $request): PushRequest
    {
        return $request->withMessage($this->worker->process(
            $request->getMessage(),
            $request->getQueueName(),
            fn(MessageInterface $message): MessageInterface => $this->queue->push($message),
        ));
    }
}
