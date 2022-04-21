<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Push\Event;

use Yiisoft\Yii\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\PushRequest;

/**
 * BeforeMiddleware event is raised before executing a middleware.
 */
final class BeforePushMiddleware
{
    /**
     * @param MiddlewarePushInterface $middleware Middleware to be executed.
     * @param PushRequest $request Request to be passed to the middleware.
     */
    public function __construct(
        private MiddlewarePushInterface $middleware,
        private PushRequest $request
    ) {
    }

    /**
     * @return MiddlewarePushInterface Middleware to be executed.
     */
    public function getMiddleware(): MiddlewarePushInterface
    {
        return $this->middleware;
    }

    /**
     * @return PushRequest Request to be passed to the middleware.
     */
    public function getRequest(): PushRequest
    {
        return $this->request;
    }
}
