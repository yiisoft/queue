<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Consume;

/**
 * Creates a middleware based on the definition provided.
 * You may implement this interface if you want to introduce custom definitions or pass additional data to
 * the middleware created.
 */
interface MiddlewareFactoryConsumeInterface
{
    /**
     * Create a middleware based on definition provided.
     *
     * @param array|callable|string $middlewareDefinition Middleware definition to use.
     */
    public function createConsumeMiddleware(callable|array|string $middlewareDefinition): MiddlewareConsumeInterface;
}
