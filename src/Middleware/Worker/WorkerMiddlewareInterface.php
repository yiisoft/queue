<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

interface WorkerMiddlewareInterface
{
    public function processWorker(WorkerRequest $request, WorkerHandlerInterface $handler): WorkerRequest;
}
