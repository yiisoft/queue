<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

use Closure;

final class MiddlewareDispatcher
{
    /**
     * Contains a middleware pipeline handler.
     *
     * @var MiddlewareStack|null The middleware stack.
     */
    private ?MiddlewareStack $stack = null;
    /**
     * @var array[]|callable[]|MiddlewareInterface[]|string[]
     */
    private array $middlewareDefinitions;

    public function __construct(
        private MiddlewareFactoryInterface $middlewareFactory,
        array|callable|string|MiddlewareInterface ...$middlewareDefinitions,
    ) {
        $this->middlewareDefinitions = array_reverse($middlewareDefinitions);
    }

    /**
     * Dispatch request through middleware to get response.
     *
     * @param Request $request Request to pass to middleware.
     * @param MessageHandlerInterface $finishHandler Handler to use in case no middleware produced response.
     */
    public function dispatch(
        Request $request,
        MessageHandlerInterface $finishHandler
    ): Request {
        if ($this->stack === null) {
            $this->stack = new MiddlewareStack($this->buildMiddlewares(), $finishHandler);
        }

        return $this->stack->handle($request);
    }

    /**
     * Returns new instance with middleware handlers replaced with the ones provided.
     * Last specified handler will be executed first.
     *
     * @param array[]|callable[]|MiddlewareInterface[]|string[] $middlewareDefinitions Each array element is:
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
            $middlewares[] = static fn (): MiddlewareInterface => $factory->createMiddleware(
                $middlewareDefinition
            );
        }

        return $middlewares;
    }
}
