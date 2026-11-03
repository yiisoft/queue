<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;

/**
 * Creates a middleware based on the definition provided.
 *
 * @template T
 */
interface WorkerMiddlewareFactoryInterface
{
    /**
     * @param mixed $middlewareDefinition Middleware definition to use.
     *
     * @throws InvalidMiddlewareDefinitionException If the definition is not supported or is invalid.
     * @psalm-param T $middlewareDefinition
     */
    public function createWorkerMiddleware(mixed $middlewareDefinition): WorkerMiddlewareInterface;
}
