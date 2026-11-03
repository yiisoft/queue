<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\Middleware\MiddlewareFactory;

use function is_array;
use function is_callable;
use function is_string;

/**
 * @template-extends MiddlewareFactory<WorkerMiddlewareInterface>
 * @template-implements WorkerMiddlewareFactoryInterface<mixed>
 */
final class WorkerMiddlewareFactory extends MiddlewareFactory implements WorkerMiddlewareFactoryInterface
{
    public function createWorkerMiddleware(mixed $middlewareDefinition): WorkerMiddlewareInterface
    {
        if ($middlewareDefinition instanceof WorkerMiddlewareInterface) {
            return $middlewareDefinition;
        }

        if (
            !is_callable($middlewareDefinition)
            && !is_array($middlewareDefinition)
            && !is_string($middlewareDefinition)
        ) {
            throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
        }

        /** @var object $middleware */
        $middleware = $this->create($middlewareDefinition);
        if (!$middleware instanceof WorkerMiddlewareInterface) {
            throw new InvalidMiddlewareDefinitionException($middlewareDefinition);
        }

        return $middleware;
    }

    protected function getInterfaceName(): string
    {
        return WorkerMiddlewareInterface::class;
    }

    protected function wrapMiddleware(callable $callback): WorkerMiddlewareInterface
    {
        return new class ($callback) implements WorkerMiddlewareInterface {
            /** @var callable */
            private readonly mixed $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function processWorker(WorkerRequest $request, WorkerHandlerInterface $handler): WorkerRequest
            {
                $response = ($this->callback)($request, $handler);
                if ($response instanceof WorkerRequest) {
                    return $response;
                }

                if ($response instanceof WorkerMiddlewareInterface) {
                    return $response->processWorker($request, $handler);
                }

                throw new InvalidMiddlewareDefinitionException($this->callback);
            }
        };
    }
}
