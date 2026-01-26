<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

use Closure;

final class MiddlewareConsumeStack implements MessageHandlerConsumeInterface
{
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var MessageHandlerConsumeInterface|null stack of middleware
     */
    private ?MessageHandlerConsumeInterface $stack = null;

    /**
     * @param Closure[] $middlewares Middlewares.
     * @param MessageHandlerConsumeInterface $finishHandler Fallback handler
     * events.
     */
    public function __construct(
        private readonly array $middlewares,
        private readonly MessageHandlerConsumeInterface $finishHandler,
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
     */
    private function wrap(Closure $middlewareFactory, MessageHandlerConsumeInterface $handler): MessageHandlerConsumeInterface
    {
        return new class ($middlewareFactory, $handler) implements MessageHandlerConsumeInterface {
            private ?MiddlewareConsumeInterface $middleware = null;

            public function __construct(
                private readonly Closure $middlewareFactory,
                private readonly MessageHandlerConsumeInterface $handler,
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
