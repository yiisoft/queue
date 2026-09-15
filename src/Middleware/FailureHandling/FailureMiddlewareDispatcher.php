<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

use Closure;

final class FailureMiddlewareDispatcher
{
    public const DEFAULT_PIPELINE = 'failure-pipeline-default';

    /**
     * Contains a middleware pipeline handler.
     *
     * @var FailureMiddlewareStack[] The middleware stack.
     */
    private array $stack = [];

    /**
     * @var array[][]|callable[][]|FailureMiddlewareInterface[][]|string[][]
     */
    private readonly array $middlewareDefinitions;

    /**
     * @param array[][]|callable[][]|FailureMiddlewareInterface[][]|string[][] $middlewareDefinitions
     */
    public function __construct(
        private readonly FailureMiddlewareFactoryInterface $middlewareFactory,
        array $middlewareDefinitions,
    ) {
        $middlewareDefinitions[self::DEFAULT_PIPELINE] ??= [];
        $this->middlewareDefinitions = $middlewareDefinitions;
    }

    /**
     * Dispatch request through middleware to get response.
     *
     * @param FailureHandlingRequest $request Request to pass to middleware.
     * @param FailureHandlerInterface $finalHandler Handler to use in case no middleware produced a response.
     */
    public function dispatch(
        FailureHandlingRequest $request,
        FailureHandlerInterface $finalHandler,
    ): FailureHandlingRequest {
        $queueName = $request->getQueueName();
        if (!isset($this->middlewareDefinitions[$queueName]) || $this->middlewareDefinitions[$queueName] === []) {
            $queueName = self::DEFAULT_PIPELINE;
        }
        $definitions = array_reverse($this->middlewareDefinitions[$queueName]);

        $this->stack[$queueName] ??= new FailureMiddlewareStack($this->buildMiddlewares(...$definitions), $finalHandler);

        return $this->stack[$queueName]->handleFailure($request);
    }

    /**
     * @psalm-return list<Closure():FailureMiddlewareInterface>
     */
    private function buildMiddlewares(array|callable|string|FailureMiddlewareInterface ...$definitions): array
    {
        $middlewares = [];
        $factory = $this->middlewareFactory;

        foreach ($definitions as $middlewareDefinition) {
            $middlewares[] = static fn(): FailureMiddlewareInterface
                => $factory->createFailureMiddleware($middlewareDefinition);
        }

        return $middlewares;
    }
}
