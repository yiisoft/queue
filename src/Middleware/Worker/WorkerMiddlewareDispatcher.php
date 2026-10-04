<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

use Closure;

final class WorkerMiddlewareDispatcher
{
    private ?WorkerMiddlewareStack $stack = null;

    /**
     * @param mixed[] $middlewareDefinitions Middleware definitions.
     */
    public function __construct(
        private readonly WorkerMiddlewareFactoryInterface $middlewareFactory,
        private readonly array $middlewareDefinitions,
        private readonly WorkerHandlerInterface $finalHandler,
    ) {}

    public function dispatch(WorkerRequest $request): WorkerRequest
    {
        $this->stack ??= new WorkerMiddlewareStack($this->buildMiddlewares(), $this->finalHandler);

        return $this->stack->handleWorker($request);
    }

    /**
     * @psalm-return list<Closure():WorkerMiddlewareInterface>
     */
    private function buildMiddlewares(): array
    {
        /** @var list<Closure():WorkerMiddlewareInterface> $middlewares */
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
