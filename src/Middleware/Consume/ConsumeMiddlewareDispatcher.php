<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

use Closure;

use function array_key_exists;

final class ConsumeMiddlewareDispatcher
{
    /**
     * Contains a middleware pipeline handlers.
     *
     * @var ConsumeMiddlewareStack[] The middleware stack divided by message types.
     */
    private array $stack = [];

    /**
     * @var array[]|callable[]|ConsumeMiddlewareInterface[]|string[]
     */
    private readonly array $middlewareDefinitions;

    public function __construct(
        private readonly ConsumeMiddlewareFactoryInterface $middlewareFactory,
        array|callable|string|ConsumeMiddlewareInterface ...$middlewareDefinitions,
    ) {
        $this->middlewareDefinitions = array_reverse($middlewareDefinitions);
    }

    /**
     * Dispatch request through middleware to get response.
     *
     * @param ConsumeRequest $request Request to pass to middleware.
     * @param ConsumeHandlerInterface $finalHandler Handler to use in case no middleware produced a response.
     */
    public function dispatch(
        ConsumeRequest $request,
        ConsumeHandlerInterface $finalHandler,
    ): ConsumeRequest {
        $type = $request->getMessage()->getType();
        if (!array_key_exists($type, $this->stack)) {
            $this->stack[$type] = new ConsumeMiddlewareStack($this->buildMiddlewares(), $finalHandler);
        }

        return $this->stack[$type]->handleConsume($request);
    }

    /**
     * @psalm-return list<Closure():ConsumeMiddlewareInterface>
     */
    private function buildMiddlewares(): array
    {
        $middlewares = [];
        $factory = $this->middlewareFactory;

        foreach ($this->middlewareDefinitions as $middlewareDefinition) {
            $middlewares[] = static fn(): ConsumeMiddlewareInterface => $factory->createConsumeMiddleware(
                $middlewareDefinition,
            );
        }

        return $middlewares;
    }
}
