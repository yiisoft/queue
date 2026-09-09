<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug\Middleware;

use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Middleware\Worker\WorkerHandlerInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;

final class WorkerDebugMiddleware implements WorkerMiddlewareInterface
{
    public function __construct(private readonly QueueCollector $collector) {}

    public function processWorker(WorkerRequest $request, WorkerHandlerInterface $handler): WorkerRequest
    {
        $this->collector->collectWorkerProcessing($request->getMessage(), $request->getQueueName());
        return $handler->handleWorker($request);
    }
}
