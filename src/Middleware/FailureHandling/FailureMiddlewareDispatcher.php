<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\FailureHandling;

use Closure;

final class FailureMiddlewareDispatcher
{
    public const DEFAULT_PIPELINE = 'failure-pipeline-default';

    /**
     * Contains a middleware pipeline handler.
     *
     * @var MiddlewareFailureStack[] The middleware stack.
     */
    private array $stack = [];

    /**
     * @var array[][]|callable[][]|MiddlewareFailureInterface[][]|string[][]
     */
    private array $middlewareDefinitions;

    /**
     * @param MiddlewareFailureFactoryInterface $middlewareFactory
     * @param array[][]|callable[][]|string[][]|MiddlewareFailureInterface[][] $middlewareDefinitions
     */
    public function __construct(
        private MiddlewareFailureFactoryInterface $middlewareFactory,
        array $middlewareDefinitions,
    ) {
        $this->middlewareDefinitions = $middlewareDefinitions;
        if (!isset($this->middlewareDefinitions[self::DEFAULT_PIPELINE])) {
            $this->middlewareDefinitions[self::DEFAULT_PIPELINE] = [];
        }
    }

    /**
     * Dispatch request through middleware to get response.
     *
     * @param string $channelName Queue channel for which to get a pipeline
     * @param FailureHandlingRequest $request Request to pass to middleware.
     * @param MessageHandlerFailureInterface $finishHandler Handler to use in case no middleware produced response.
     */
    public function dispatch(
        string $channelName,
        FailureHandlingRequest $request,
        MessageHandlerFailureInterface $finishHandler
    ): FailureHandlingRequest {
        // FIXME I can get channel name from the $request
        if (!isset($this->middlewareDefinitions[$channelName]) || $this->middlewareDefinitions[$channelName] === []) {
            $channelName = self::DEFAULT_PIPELINE;
        }
        $definitions = array_reverse($this->middlewareDefinitions[$channelName]);

        if (!isset($this->stack[$channelName])) {
            $this->stack[$channelName] = new MiddlewareFailureStack($this->buildMiddlewares($definitions), $finishHandler);
        }

        return $this->stack[$channelName]->handleFailure($request);
    }

    /**
     * Returns new instance with middleware handlers replaced with the ones provided.
     * Last specified handler will be executed first.
     *
     * @param array[][]|callable[][]|MiddlewareFailureInterface[][]|string[][] $middlewareDefinitions Each array element is:
     *
     * - A name of PSR-15 middleware class. The middleware instance will be obtained from container executed.
     * - A callable with `function(ServerRequestInterface $request, RequestHandlerInterface $handler):
     *     ResponseInterface` signature.
     * - A controller handler action in format `[TestController::class, 'index']`. `TestController` instance will
     *   be created and `index()` method will be executed.
     * - A function returning a middleware. The middleware returned will be executed.
     *
     * For handler action and callable
     * typed parameters are automatically injected using dependency injection container.
     * Current request and handler could be obtained by type-hinting for {@see ServerRequestInterface}
     * and {@see RequestHandlerInterface}.
     *
     * @return self
     */
    public function withMiddlewares(array $middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->middlewareDefinitions = array_reverse($middlewareDefinitions);

        // Fixes a memory leak.
        unset($instance->stack);
        $instance->stack = [];

        return $instance;
    }

    /**
     * @return Closure[]
     */
    private function buildMiddlewares(array|callable|string|MiddlewareFailureInterface ...$definitions): array
    {
        $middlewares = [];
        $factory = $this->middlewareFactory;

        foreach ($definitions as $middlewareDefinition) {
            $middlewares[] = static fn (): MiddlewareFailureInterface =>
                $factory->createFailureMiddleware($middlewareDefinition);
        }

        return $middlewares;
    }
}
