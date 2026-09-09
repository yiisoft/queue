<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

use Closure;

/**
 * Builds and executes a worker middleware stack.
 */
final class WorkerMiddlewareStack implements WorkerHandlerInterface
{
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var WorkerHandlerInterface|null Stack of middleware.
     */
    private ?WorkerHandlerInterface $stack = null;

    /**
     * @param Closure[] $middlewares Middleware factories.
     * @param WorkerHandlerInterface $finishHandler Final handler invoked after all middlewares are processed.
     * @psalm-param list<Closure():WorkerMiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly array $middlewares,
        private WorkerHandlerInterface $finishHandler,
    ) {}

    public function handleWorker(WorkerRequest $request, ?WorkerHandlerInterface $finishHandler = null): WorkerRequest
    {
        if ($finishHandler !== null) {
            $this->finishHandler = $finishHandler;
        }
        $this->stack ??= $this->build();

        return $this->stack->handleWorker($request);
    }

    public function handleFinish(WorkerRequest $request): WorkerRequest
    {
        return $this->finishHandler->handleWorker($request);
    }

    private function build(): WorkerHandlerInterface
    {
        $handler = new class ($this) implements WorkerHandlerInterface {
            public function __construct(private readonly WorkerMiddlewareStack $stack) {}

            public function handleWorker(WorkerRequest $request): WorkerRequest
            {
                return $this->stack->handleFinish($request);
            }
        };

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
