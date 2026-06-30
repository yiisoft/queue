<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Closure;
use Yiisoft\Queue\Message\MessageInterface;

/**
 * @internal
 */
final class PushMiddlewareStack implements PushHandlerInterface
{
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var PushHandlerInterface|null stack of middleware
     */
    private ?PushHandlerInterface $stack = null;

    /**
     * @param Closure[] $middlewares Middlewares.
     * @param PushHandlerInterface $finishHandler Final handler invoked after all middlewares are processed.
     *
     * @psalm-param list<Closure():PushMiddlewareInterface> $middlewares
     */
    public function __construct(
        private readonly array $middlewares,
        private readonly PushHandlerInterface $finishHandler,
    ) {}

    public function handlePush(MessageInterface $message): MessageInterface
    {
        $this->stack ??= $this->build();
        return $this->stack->handlePush($message);
    }

    private function build(): PushHandlerInterface
    {
        $handler = $this->finishHandler;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $handler = $this->wrap($middleware, $handler);
        }

        return $handler;
    }

    /**
     * Wrap handler by middlewares.
     *
     * @psalm-param Closure():PushMiddlewareInterface $middlewareFactory
     */
    private function wrap(Closure $middlewareFactory, PushHandlerInterface $handler): PushHandlerInterface
    {
        return new class ($middlewareFactory, $handler) implements PushHandlerInterface {
            private ?PushMiddlewareInterface $middleware = null;

            /**
             * @psalm-param Closure():PushMiddlewareInterface $middlewareFactory
             */
            public function __construct(
                private readonly Closure $middlewareFactory,
                private readonly PushHandlerInterface $handler,
            ) {}

            public function handlePush(MessageInterface $message): MessageInterface
            {
                if ($this->middleware === null) {
                    $this->middleware = ($this->middlewareFactory)();
                }

                return $this->middleware->processPush($message, $this->handler);
            }
        };
    }
}
