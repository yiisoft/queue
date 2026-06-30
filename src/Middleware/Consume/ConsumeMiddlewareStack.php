<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

use Closure;

final class ConsumeMiddlewareStack implements ConsumeHandlerInterface
{
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var ConsumeHandlerInterface|null stack of middleware
     */
    private ?ConsumeHandlerInterface $stack = null;

    /**
     * @param Closure[] $middlewares Middlewares.
     * @param ConsumeHandlerInterface $finishHandler Fallback handler
     * events.
     *
     * @psalm-param list<Closure():ConsumeMiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly array $middlewares,
        private readonly ConsumeHandlerInterface $finishHandler,
    ) {}

    public function handleConsume(ConsumeRequest $request): ConsumeRequest
    {
        if ($this->stack === null) {
            $this->build();
        }

        /** @psalm-suppress PossiblyNullReference */
        return $this->stack->handleConsume($request);
    }

    private function build(): void
    {
        $handler = $this->finishHandler;

        foreach ($this->middlewares as $middleware) {
            $handler = $this->wrap($middleware, $handler);
        }

        $this->stack = $handler;
    }

    /**
     * Wrap handler by middlewares.
     *
     * @psalm-param Closure():ConsumeMiddlewareInterface $middlewareFactory
     */
    private function wrap(Closure $middlewareFactory, ConsumeHandlerInterface $handler): ConsumeHandlerInterface
    {
        return new class ($middlewareFactory, $handler) implements ConsumeHandlerInterface {
            private ?ConsumeMiddlewareInterface $middleware = null;

            /**
             * @psalm-param Closure():ConsumeMiddlewareInterface $middlewareFactory
             */
            public function __construct(
                private readonly Closure $middlewareFactory,
                private readonly ConsumeHandlerInterface $handler,
            ) {}

            public function handleConsume(ConsumeRequest $request): ConsumeRequest
            {
                if ($this->middleware === null) {
                    $this->middleware = ($this->middlewareFactory)();
                }

                return $this->middleware->processConsume($request, $this->handler);
            }
        };
    }
}
