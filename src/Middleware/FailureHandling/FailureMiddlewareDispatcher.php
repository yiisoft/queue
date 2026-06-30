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
     * @param array[][]|callable[][]|FailureMiddlewareInterface[][]|string[][] $middlewareDefinitions
     */
    public function __construct(
        private readonly FailureMiddlewareFactoryInterface $middlewareFactory,
        private array $middlewareDefinitions,
    ) {
        $this->init();
    }

    /**
     * Dispatch request through middleware to get response.
     *
     * @param FailureHandlingRequest $request Request to pass to middleware.
     * @param FailureHandlerInterface $finishHandler Handler to use in case no middleware produced a response.
     */
    public function dispatch(
        FailureHandlingRequest $request,
        FailureHandlerInterface $finishHandler,
    ): FailureHandlingRequest {
        $queueName = $request->getQueue()->getName();
        if (!isset($this->middlewareDefinitions[$queueName]) || $this->middlewareDefinitions[$queueName] === []) {
            $queueName = self::DEFAULT_PIPELINE;
        }
        $definitions = array_reverse($this->middlewareDefinitions[$queueName]);

        if (!isset($this->stack[$queueName])) {
            $this->stack[$queueName] = new FailureMiddlewareStack($this->buildMiddlewares(...$definitions), $finishHandler);
        }

        return $this->stack[$queueName]->handleFailure($request);
    }

    /**
     * Returns new instance with middleware handlers replaced with the ones provided.
     * The last specified handler will be executed first.
     *
     * @param array[][]|callable[][]|FailureMiddlewareInterface[][]|string[][] $middlewareDefinitions Each array element is:
     *
     * - A name of a middleware class. The middleware instance will be obtained from container executed.
     * - A callable with `function(ServerRequestInterface $request, RequestHandlerInterface $handler):
     *     ResponseInterface` signature.
     * - A "callable-like" array in format `[FooMiddleware::class, 'index']`. `FooMiddleware` instance will
     *   be created and `index()` method will be executed.
     * - A function returning a middleware. The middleware returned will be executed.
     *
     * For callables typed parameters are automatically injected using dependency injection container.
     *
     * @return self New instance of the {@see FailureMiddlewareDispatcher}
     */
    public function withMiddlewares(array $middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->middlewareDefinitions = $middlewareDefinitions;

        // Fixes a memory leak.
        unset($instance->stack);
        $instance->stack = [];

        $instance->init();

        return $instance;
    }

    private function init(): void
    {
        if (!isset($this->middlewareDefinitions[self::DEFAULT_PIPELINE])) {
            $this->middlewareDefinitions[self::DEFAULT_PIPELINE] = [];
        }
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
