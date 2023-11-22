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
     * @param array[][]|callable[][]|MiddlewareFailureInterface[][]|string[][] $middlewareDefinitions
     */
    public function __construct(
        private MiddlewareFactoryFailureInterface $middlewareFactory,
        private array $middlewareDefinitions,
    ) {
        $this->init();
    }

    /**
     * Dispatch request through middleware to get response.
     *
     * @param FailureHandlingRequest $request Request to pass to middleware.
     * @param MessageFailureHandlerInterface $finishHandler Handler to use in case no middleware produced response.
     */
    public function dispatch(
        FailureHandlingRequest $request,
        MessageFailureHandlerInterface $finishHandler
    ): FailureHandlingRequest {
        $channelName = $request->getQueue()->getChannelName();
        if (!isset($this->middlewareDefinitions[$channelName]) || $this->middlewareDefinitions[$channelName] === []) {
            $channelName = self::DEFAULT_PIPELINE;
        }
        $definitions = array_reverse($this->middlewareDefinitions[$channelName]);

        if (!isset($this->stack[$channelName])) {
            $this->stack[$channelName] = new MiddlewareFailureStack($this->buildMiddlewares(...$definitions), $finishHandler);
        }

        return $this->stack[$channelName]->handleFailure($request);
    }

    /**
     * Returns new instance with middleware handlers replaced with the ones provided.
     * Last specified handler will be executed first.
     *
     * @param array[][]|callable[][]|MiddlewareFailureInterface[][]|string[][] $middlewareDefinitions Each array element is:
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
