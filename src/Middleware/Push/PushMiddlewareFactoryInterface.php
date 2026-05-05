<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;

/**
 * Creates a middleware based on the definition provided.
 * You may implement this interface if you want to introduce custom definitions or pass additional data to
 * the middleware created.
 *
 * @template T
 */
interface PushMiddlewareFactoryInterface
{
    /**
     * Create a middleware based on the definition provided.
     *
     * @param mixed $middlewareDefinition Middleware definition to use.
     *
     * @throws InvalidMiddlewareDefinitionException If the definition is not supported or is invalid.
     *
     * @return PushMiddlewareInterface
     *
     * @psalm-param T $middlewareDefinition
     */
    public function createPushMiddleware(mixed $middlewareDefinition): PushMiddlewareInterface;
}
