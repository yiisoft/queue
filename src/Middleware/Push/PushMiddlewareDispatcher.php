<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

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
        private readonly MiddlewareFactoryPushInterface $middlewareFactory,
        array|callable|string|MiddlewarePushInterface ...$middlewareDefinitions,
    ) {
        $this->middlewareDefinitions = array_reverse($middlewareDefinitions);
    }

    /**
     * Dispatch request through middleware to get response.
     *
     * @param PushRequest $request Request to pass to middleware.
     * @param MessageHandlerPushInterface $finishHandler Handler to use in case no middleware produced a response.
     */
    public function dispatch(
        PushRequest $request,
        MessageHandlerPushInterface $finishHandler,
    ): PushRequest {
        if ($this->stack === null) {
            $this->stack = new MiddlewarePushStack($this->buildMiddlewares(), $finishHandler);
        }

        return $this->stack->handlePush($request);
    }

    /**
     * Returns new instance with middleware handlers replaced with the ones provided.
     * The last specified handler will be executed first.
     *
     * @param array[]|callable[]|MiddlewarePushInterface[]|string[] $middlewareDefinitions Each array element is:
     *
     * - A name of a middleware class. The middleware instance will be obtained from container executed.
     * - A callable with `function(ServerRequestInterface $request, RequestHandlerInterface $handler):
     *     ResponseInterface` signature.
     * - A "callable-like" array in format `[FooMiddleware::class, 'index']`. `FooMiddleware` instance will
     *   be created and `index()` method will be executed.
     * - A function returning a middleware. The middleware returned will be executed.
     *
     * For callables typed parameters are automatically injected using dependency injection container.
     *
     * @return self New instance of the {@see PushMiddlewareDispatcher}
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
            $middlewares[] = static fn(): MiddlewarePushInterface => $factory->createPushMiddleware(
                $middlewareDefinition,
            );
        }

        return $middlewares;
    }
}
