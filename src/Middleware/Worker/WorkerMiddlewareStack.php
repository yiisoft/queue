<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

use Closure;

final class WorkerMiddlewareStack implements WorkerHandlerInterface
{
    private ?WorkerHandlerInterface $stack = null;

    /**
     * @param Closure[] $middlewares Middleware factories.
     * @psalm-param list<Closure():WorkerMiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly array $middlewares,
        private readonly WorkerHandlerInterface $finalHandler,
    ) {}

    public function handleWorker(WorkerRequest $request): WorkerRequest
    {
        $this->stack ??= $this->build();

        return $this->stack->handleWorker($request);
    }

    private function build(): WorkerHandlerInterface
    {
        $handler = $this->finalHandler;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $handler = $this->wrap($middleware, $handler);
        }

        return $handler;
    }

    /**
     * @psalm-param Closure():WorkerMiddlewareInterface $middlewareFactory
     */
    private function wrap(Closure $middlewareFactory, WorkerHandlerInterface $handler): WorkerHandlerInterface
    {
        return new class ($middlewareFactory, $handler) implements WorkerHandlerInterface {
            private ?WorkerMiddlewareInterface $middleware = null;

            /**
             * @psalm-param Closure():WorkerMiddlewareInterface $middlewareFactory
             */
            public function __construct(
                private readonly Closure $middlewareFactory,
                private readonly WorkerHandlerInterface $handler,
            ) {}

            public function handleWorker(WorkerRequest $request): WorkerRequest
            {
                $this->middleware ??= ($this->middlewareFactory)();

                return $this->middleware->processWorker($request, $this->handler);
            }
        };
    }
}
