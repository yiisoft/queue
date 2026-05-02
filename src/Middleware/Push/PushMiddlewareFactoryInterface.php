<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

/**
 * Creates a middleware based on the definition provided.
 * You may implement this interface if you want to introduce custom definitions or pass additional data to
 * the middleware created.
 */
interface PushMiddlewareFactoryInterface
{
    /**
     * Create a middleware based on definition provided.
     *
     * @param array|callable|PushMiddlewareInterface|string $middlewareDefinition Middleware definition to use.
     *
     * @return PushMiddlewareInterface
     */
    public function createPushMiddleware(callable|array|string|PushMiddlewareInterface $middlewareDefinition): PushMiddlewareInterface;
}
