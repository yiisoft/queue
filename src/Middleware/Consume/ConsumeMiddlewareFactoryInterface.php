<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

/**
 * Creates a middleware based on the definition provided.
 * You may implement this interface if you want to introduce custom definitions or pass additional data to
 * the middleware created.
 */
interface ConsumeMiddlewareFactoryInterface
{
    /**
     * Create a middleware based on definition provided.
     *
     * @param array|callable|ConsumeMiddlewareInterface|string $middlewareDefinition Middleware definition to use.
     *
     * @return ConsumeMiddlewareInterface
     */
    public function createConsumeMiddleware(callable|array|string|ConsumeMiddlewareInterface $middlewareDefinition): ConsumeMiddlewareInterface;
}
