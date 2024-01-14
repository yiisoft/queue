<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

/**
 * Creates a middleware based on the definition provided.
 * You may implement this interface if you want to introduce custom definitions or pass additional data to
 * the middleware created.
 */
interface MiddlewareFactoryInterface
{
    /**
     * Create a middleware based on definition provided.
     *
     * @param array|callable|MiddlewareInterface|string $middlewareDefinition Middleware definition to use.
     *
     * @return MiddlewareInterface
     */
    public function createMiddleware(callable|array|string|MiddlewareInterface $middlewareDefinition): MiddlewareInterface;
}
