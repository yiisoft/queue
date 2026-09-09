<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

use Closure;

/**
 * Dispatches worker requests through the configured middleware pipeline.
 */
final class WorkerMiddlewareDispatcher
{
    /**
     * Contains a middleware pipeline handler.
     *
     * @var WorkerMiddlewareStack|null The middleware stack.
     */
    private ?WorkerMiddlewareStack $stack = null;

    /**
     * @param WorkerMiddlewareFactoryInterface $middlewareFactory Factory used to instantiate middleware.
     * @param mixed[] $middlewareDefinitions Middleware definitions in execution order.
     */
    public function __construct(
        private readonly WorkerMiddlewareFactoryInterface $middlewareFactory,
        private array $middlewareDefinitions = [],
    ) {}

    public function dispatch(WorkerRequest $request, WorkerHandlerInterface $finishHandler): WorkerRequest
    {
        $this->stack ??= new WorkerMiddlewareStack($this->buildMiddlewares(), $finishHandler);

        return $this->stack->handleWorker($request, $finishHandler);
    }

    /**
     * Returns a new instance with middleware definitions replaced.
     *
     * @param mixed[] $middlewareDefinitions Middleware definitions in execution order.
     */
    public function withMiddlewares(array $middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->middlewareDefinitions = $middlewareDefinitions;

        // Fixes a memory leak.
        unset($instance->stack);
        $instance->stack = null;

        return $instance;
    }

    public function hasMiddlewares(): bool
    {
        return $this->middlewareDefinitions !== [];
    }

    /**
     * @psalm-return list<Closure():WorkerMiddlewareInterface>
     */
    private function buildMiddlewares(): array
    {
        $middlewares = [];
        $factory = $this->middlewareFactory;

        foreach ($this->middlewareDefinitions as $middlewareDefinition) {
            $middlewares[] = static fn(): WorkerMiddlewareInterface => $factory->createWorkerMiddleware(
                $middlewareDefinition,
            );
        }

        return $middlewares;
    }
}
