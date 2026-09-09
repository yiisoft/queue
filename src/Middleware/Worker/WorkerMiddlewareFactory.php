<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

use Yiisoft\Queue\Middleware\InvalidMiddlewareDefinitionException;
use Yiisoft\Queue\Middleware\MiddlewareFactory;

use function is_array;
use function is_callable;
use function is_string;

/**
 * Creates a worker middleware based on its definition.
 *
 * @template-extends MiddlewareFactory<WorkerMiddlewareInterface>
 */
final class WorkerMiddlewareFactory extends MiddlewareFactory implements WorkerMiddlewareFactoryInterface
{
    /**
     * @param mixed $definition
     * @throws InvalidMiddlewareDefinitionException
     */
    public function createWorkerMiddleware(mixed $definition): WorkerMiddlewareInterface
    {
        if ($definition instanceof WorkerMiddlewareInterface) {
            return $definition;
        }

        if (!is_callable($definition) && !is_array($definition) && !is_string($definition)) {
            throw new InvalidMiddlewareDefinitionException($definition);
        }

        /** @var callable|array|string $definition */
        $middleware = $this->create($definition);

        if (!$middleware instanceof WorkerMiddlewareInterface) {
            throw new InvalidMiddlewareDefinitionException($definition);
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
