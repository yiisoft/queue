<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Push;

/**
 * Creates a middleware based on the definition provided.
 * You may implement this interface if you want to introduce custom definitions or pass additional data to
 * the middleware created.
 */
interface MiddlewareFactoryPushInterface
{
    /**
     * Create a middleware based on definition provided.
     *
     * @param array|callable|MiddlewarePushInterface|string $middlewareDefinition Middleware definition to use.
     *
     * @return MiddlewarePushInterface
     */
    public function createPushMiddleware(callable|array|string|MiddlewarePushInterface $middlewareDefinition): MiddlewarePushInterface;
}
