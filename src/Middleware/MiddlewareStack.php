<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

use Closure;

final class MiddlewareStack implements MessageHandlerInterface
{
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var MessageHandlerInterface|null stack of middleware
     */
    private ?MessageHandlerInterface $stack = null;

    /**
     * @param Closure[] $middlewares Middlewares.
     * @param MessageHandlerInterface $finishHandler Fallback handler
     * events.
     */
    public function __construct(
        private array $middlewares,
        private MessageHandlerInterface $finishHandler,
    ) {
    }

    public function handle(Request $request): Request
    {
        if ($this->stack === null) {
            $this->build();
        }

        /** @psalm-suppress PossiblyNullReference */
        return $this->stack->handle($request);
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
    private function wrap(Closure $middlewareFactory, MessageHandlerInterface $handler): MessageHandlerInterface
    {
        return new class ($middlewareFactory, $handler) implements MessageHandlerInterface {
            private ?MiddlewareInterface $middleware = null;

            public function __construct(
                private Closure $middlewareFactory,
                private MessageHandlerInterface $handler,
            ) {
            }

            public function handle(Request $request): Request
            {
                if ($this->middleware === null) {
                    $this->middleware = ($this->middlewareFactory)();
                }

                return $this->middleware->process($request, $this->handler);
            }
        };
    }
}
