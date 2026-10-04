<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Worker\WorkerInterface;

/**
 * @internal
 */
final class SynchronousPushHandler implements PushHandlerInterface
{
    public function __construct(
        private readonly WorkerInterface $worker,
        private readonly string $queueName,
    ) {}

    public function handlePush(PushRequest $request): PushRequest
    {
        $this->worker->process($request->getMessage(), $request->getQueueName());

        return $request;
    }
}
