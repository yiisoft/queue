<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Closure;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Queue;

/**
 * @internal Used internally by {@see Queue}.
 */
final class PushMiddlewareDispatcher
{
    /**
     * Contains a middleware pipeline handler.
     *
     * @var PushMiddlewareStack|null The middleware stack.
     */
    private ?PushMiddlewareStack $stack = null;

    /**
     * @param PushMiddlewareFactoryInterface $middlewareFactory Factory used to instantiate middleware.
     * @param mixed[] $middlewareDefinitions Middleware definitions.
     * @param PushHandlerInterface $finishHandler Finish message handler.
     */
    public function __construct(
        private readonly PushMiddlewareFactoryInterface $middlewareFactory,
        private array $middlewareDefinitions,
        private PushHandlerInterface $finishHandler,
    ) {}

    /**
     * Dispatch message through middleware to get response.
     *
     * @param MessageInterface $message Message to pass to middleware.
     */
    public function dispatch(MessageInterface $message): MessageInterface
    {
        if ($this->stack === null) {
            $this->stack = new PushMiddlewareStack($this->buildMiddlewares(), $this->finishHandler);
        }

        return $this->stack->handlePush($message);
    }

    public function withFinishHandler(PushHandlerInterface $finishHandler): self
    {
        $instance = clone $this;
        $instance->finishHandler = $finishHandler;

        // Fixes a memory leak.
        unset($instance->stack);
        $instance->stack = null;

        return $instance;
    }

    /**
     * Returns new instance with middleware handlers replaced with the ones provided.
     *
     * @param mixed[] $middlewareDefinitions Middleware definitions.
     *
     * @return self New instance of the {@see PushMiddlewareDispatcher}
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

    /**
     * Returns a new instance with additional middleware handlers added to the existing ones.
     *
     * @param mixed[] $middlewareDefinitions Middleware definitions.
     *
     * @return self New instance of the {@see PushMiddlewareDispatcher}
     */
    public function withMiddlewaresAdded(array $middlewareDefinitions): self
    {
        return $this->withMiddlewares([...$this->middlewareDefinitions, ...$middlewareDefinitions]);
    }

    /**
     * @return bool Whether there are middleware defined in the dispatcher.
     */
    public function hasMiddlewares(): bool
    {
        return $this->middlewareDefinitions !== [];
    }

    /**
     * @psalm-return list<Closure():PushMiddlewareInterface>
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
