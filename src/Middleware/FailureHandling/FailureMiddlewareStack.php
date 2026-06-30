<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Closure;

final class FailureMiddlewareStack implements FailureHandlerInterface
{
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var FailureHandlerInterface|null stack of middleware
     */
    private ?FailureHandlerInterface $stack = null;

    /**
     * @param Closure[] $middlewares Middlewares.
     * @param FailureHandlerInterface $finishHandler Fallback handler
     * events.
     *
     * @psalm-param list<Closure():FailureMiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly array $middlewares,
        private readonly FailureHandlerInterface $finishHandler,
    ) {}

    public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest
    {
        $this->stack ??= $this->build();
        return $this->stack->handleFailure($request);
    }

    private function build(): FailureHandlerInterface
    {
        $handler = $this->finishHandler;

        foreach ($this->middlewares as $middleware) {
            $handler = $this->wrap($middleware, $handler);
        }

        return $handler;
    }

    /**
     * Wrap handler by middlewares.
     * @psalm-param Closure():FailureMiddlewareInterface $middlewareFactory
     */
    private function wrap(Closure $middlewareFactory, FailureHandlerInterface $handler): FailureHandlerInterface
    {
        return new class ($middlewareFactory, $handler) implements FailureHandlerInterface {
            private ?FailureMiddlewareInterface $middleware = null;

            /**
             * @psalm-param Closure():FailureMiddlewareInterface $middlewareFactory
             */
            public function __construct(
                private readonly Closure $middlewareFactory,
                private readonly FailureHandlerInterface $handler,
            ) {}

            public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest
            {
                if ($this->middleware === null) {
                    $this->middleware = ($this->middlewareFactory)();
                }

                return $this->middleware->processFailure($request, $this->handler);
            }
        };
    }
}
