<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

interface WorkerHandlerInterface
{
    public function handleWorker(WorkerRequest $request): WorkerRequest;
}
