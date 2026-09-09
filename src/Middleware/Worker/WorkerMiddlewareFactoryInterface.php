<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

interface WorkerMiddlewareFactoryInterface
{
    public function createWorkerMiddleware(mixed $definition): WorkerMiddlewareInterface;
}
