<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Closure;
use Yiisoft\Queue\Message\MessageInterface;

final class PushMiddlewareDispatcher
{
    /**
     * Contains a middleware pipeline handler.
     *
     * @var PushMiddlewareStack|null The middleware stack.
     */
    private ?PushMiddlewareStack $stack = null;
    /**
     * @var array[]|callable[]|PushMiddlewareInterface[]|string[]
     */
    private array $middlewareDefinitions;

    public function __construct(
        private readonly PushMiddlewareFactoryInterface $middlewareFactory,
        array|callable|string|PushMiddlewareInterface ...$middlewareDefinitions,
    ) {
        $this->middlewareDefinitions = array_reverse($middlewareDefinitions);
    }

    /**
     * Dispatch message through middleware to get response.
     *
     * @param MessageInterface $message Message to pass to middleware.
     * @param PushHandlerInterface $finishHandler Handler to use in case no middleware produced a response.
     */
    public function dispatch(
        MessageInterface $message,
        PushHandlerInterface $finishHandler,
    ): MessageInterface {
        if ($this->stack === null) {
            $this->stack = new PushMiddlewareStack($this->buildMiddlewares(), $finishHandler);
        }

        return $this->stack->handlePush($message);
    }

    /**
     * Returns new instance with middleware handlers replaced with the ones provided.
     *
     * @param array[]|callable[]|PushMiddlewareInterface[]|string[] $middlewareDefinitions Each array element is:
     *
     * - A name of a middleware class. The middleware instance will be obtained from container executed.
     * - A callable with `function(MessageInterface $message, PushHandlerInterface $handler):
     *     MessageInterface` signature.
     * - A "callable-like" array in format `[FooMiddleware::class, 'index']`. `FooMiddleware` instance will
     *   be created and `index()` method will be executed.
     * - A function returning a middleware. The middleware returned will be executed.
     *
     * For callables typed parameters are automatically injected using dependency injection container.
     *
     * @return self New instance of the {@see PushMiddlewareDispatcher}
     */
    public function withMiddlewares(array $middlewareDefinitions): self
    {
        $instance = clone $this;
        $instance->middlewareDefinitions = array_reverse($middlewareDefinitions);

        // Fixes a memory leak.
        unset($instance->stack);
        $instance->stack = null;

        return $instance;
    }

    /**
     * Returns a new instance with additional middleware handlers added to the existing ones.
     *
     * @param array[]|callable[]|PushMiddlewareInterface[]|string[] $middlewareDefinitions Each array element is:
     *
     * - A name of a middleware class. The middleware instance will be obtained from container executed.
     * - A callable with `function(MessageInterface $message, PushHandlerInterface $handler):
     *     MessageInterface` signature.
     * - A "callable-like" array in format `[FooMiddleware::class, 'index']`. `FooMiddleware` instance will
     *   be created and `index()` method will be executed.
     * - A function returning a middleware. The middleware returned will be executed.
     *
     * For callables typed parameters are automatically injected using dependency injection container.
     *
     * @return self New instance of the {@see PushMiddlewareDispatcher}
     */
    public function withMiddlewaresAdded(array $middlewareDefinitions): self
    {
        return $this->withMiddlewares([...array_reverse($this->middlewareDefinitions), ...$middlewareDefinitions]);
    }

    /**
     * @return bool Whether there are middleware defined in the dispatcher.
     */
    public function hasMiddlewares(): bool
    {
        return $this->middlewareDefinitions !== [];
    }

    /**
     * @return Closure[]
     */
    private function buildMiddlewares(): array
    {
        $middlewares = [];
        $factory = $this->middlewareFactory;

        foreach ($this->middlewareDefinitions as $middlewareDefinition) {
            $middlewares[] = static fn(): PushMiddlewareInterface => $factory->createPushMiddleware(
                $middlewareDefinition,
            );
        }

        return $middlewares;
    }
}
