<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Consume\Event;

use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Consume\MiddlewareConsumeInterface;

/**
 * BeforeMiddleware event is raised before executing a middleware.
 */
final class BeforeConsumeMiddleware
{
    /**
     * @param MiddlewareConsumeInterface $middleware Middleware to be executed.
     * @param ConsumeRequest $request Request to be passed to the middleware.
     */
    public function __construct(
        private MiddlewareConsumeInterface $middleware,
        private ConsumeRequest $request
    ) {
    }

    /**
     * @return MiddlewareConsumeInterface Middleware to be executed.
     */
    public function getMiddleware(): MiddlewareConsumeInterface
    {
        return $this->middleware;
    }

    /**
     * @return ConsumeRequest Request to be passed to the middleware.
     */
    public function getRequest(): ConsumeRequest
    {
        return $this->request;
    }
}
