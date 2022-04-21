<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Push;

use Closure;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Yii\Queue\Middleware\Push\Event\AfterMiddleware;
use Yiisoft\Yii\Queue\Middleware\Push\Event\BeforeMiddleware;

final class MiddlewarePushStack implements MessageHandlerPushInterface
{
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var MessageHandlerPushInterface|null stack of middleware
     */
    private ?MessageHandlerPushInterface $stack = null;

    /**
     * @param Closure[] $middlewares Middlewares.
     * @param MessageHandlerPushInterface $finishHandler Fallback handler
     * @param EventDispatcherInterface|null $dispatcher Event dispatcher to use for triggering before/after middleware
     * events.
     */
    public function __construct(
        private array $middlewares,
        private MessageHandlerPushInterface $finishHandler,
        private ?EventDispatcherInterface $dispatcher = null,
    ) {
    }

    public function handlePush(PushRequest $request): PushRequest
    {
        if ($this->stack === null) {
            $this->build();
        }

        /** @psalm-suppress PossiblyNullReference */
        return $this->stack->handlePush($request);
    }

    private function build(): void
    {
        $handler = $this->finishHandler;

        foreach ($this->middlewares as $middleware) {
            $handler = $this->wrap($middleware, $handler);
        }

        $this->stack = $handler;
    }

    /**
     * Wrap handler by middlewares.
     */
    private function wrap(Closure $middlewareFactory, MessageHandlerPushInterface $handler): MessageHandlerPushInterface
    {
        return new class ($middlewareFactory, $handler, $this->dispatcher) implements MessageHandlerPushInterface {
            private ?MiddlewarePushInterface $middleware = null;

            public function __construct(
                private Closure $middlewareFactory,
                private MessageHandlerPushInterface $handler,
                private ?EventDispatcherInterface $dispatcher
            ) {
            }

            public function handlePush(PushRequest $request): PushRequest
            {
                if ($this->middleware === null) {
                    $this->middleware = ($this->middlewareFactory)();
                }

                $this->dispatcher?->dispatch(new BeforeMiddleware($this->middleware, $request));

                try {
                    return $request = $this->middleware->processPush($request, $this->handler);
                } finally {
                    $this->dispatcher?->dispatch(new AfterMiddleware($this->middleware, $request));
                }
            }
        };
    }
}
