<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Push;

use Closure;

final class PushMiddlewareDispatcher
{
    /**
     * Contains a middleware pipeline handler.
     *
     * @var MiddlewarePushStack|null The middleware stack.
     */
    private ?MiddlewarePushStack $stack = null;
    /**
     * @var array[]|callable[]|MiddlewarePushInterface[]|string[]
     */
    private array $middlewareDefinitions;

    public function __construct(
        private MiddlewareFactoryPushInterface $middlewareFactory,
        array|callable|string|MiddlewarePushInterface ...$middlewareDefinitions,
    ) {
        $this->middlewareDefinitions = array_reverse($middlewareDefinitions);
    }

    /**
     * Dispatch request through middleware to get response.
     *
     * @param PushRequest $request Request to pass to middleware.
     * @param MessageHandlerPushInterface $finishHandler Handler to use in case no middleware produced response.
     */
    public function dispatch(
        PushRequest $request,
        MessageHandlerPushInterface $finishHandler
    ): PushRequest {
        if ($this->stack === null) {
            $this->stack = new MiddlewarePushStack($this->buildMiddlewares(), $finishHandler);
        }

        return $this->stack->handlePush($request);
    }

    /**
     * Returns new instance with middleware handlers replaced with the ones provided.
     * Last specified handler will be executed first.
     *
     * @param array[]|callable[]|MiddlewarePushInterface[]|string[] $middlewareDefinitions Each array element is:
     *
     * - A name of PSR-15 middleware class. The middleware instance will be obtained from container executed.
     * - A callable with `function(ServerRequestInterface $request, RequestHandlerInterface $handler):
     *     ResponseInterface` signature.
     * - A controller handler action in format `[TestController::class, 'index']`. `TestController` instance will
     *   be created and `index()` method will be executed.
     * - A function returning a middleware. The middleware returned will be executed.
     *
     * For handler action and callable
     * typed parameters are automatically injected using dependency injection container.
     * Current request and handler could be obtained by type-hinting for {@see ServerRequestInterface}
     * and {@see RequestHandlerInterface}.
     *
     * @return self
     */
    public function withMiddlewares(array $middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->middlewareDefinitions = array_reverse($middlewareDefinitions);

        // Fixes a memory leak.
        unset($instance->stack);
        $instance->stack = null;

        return $instance;
    }

    /**
     * @return bool Whether there are middleware defined in the dispatcher.
     */
    public function hasMiddlewares(): bool
    {
        return $this->middlewareDefinitions !== [];
    }

    /**
     * @return Closure[]
     */
    private function buildMiddlewares(): array
    {
        $middlewares = [];
        $factory = $this->middlewareFactory;

        foreach ($this->middlewareDefinitions as $middlewareDefinition) {
            $middlewares[] = static fn (): MiddlewarePushInterface => $factory->createPushMiddleware(
                $middlewareDefinition
            );
        }

        return $middlewares;
    }
}
