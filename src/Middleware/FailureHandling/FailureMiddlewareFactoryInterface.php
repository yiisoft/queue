<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

/**
 * Creates a middleware based on the definition provided.
 * You may implement this interface if you want to introduce custom definitions or pass additional data to
 * the middleware created.
 */
interface FailureMiddlewareFactoryInterface
{
    /**
     * Create a middleware based on definition provided.
     *
     * @param array|callable|FailureMiddlewareInterface|string $middlewareDefinition Middleware definition to use.
     *
     * @return FailureMiddlewareInterface
     */
    public function createFailureMiddleware(callable|array|string|FailureMiddlewareInterface $middlewareDefinition): FailureMiddlewareInterface;
}
