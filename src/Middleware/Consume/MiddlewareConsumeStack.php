<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Consume;

use Closure;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;
use Yiisoft\Yii\Queue\Middleware\Push\Event\AfterPushMiddleware;
use Yiisoft\Yii\Queue\Middleware\Push\Event\BeforePushMiddleware;

final class MiddlewareConsumeStack implements MessageHandlerConsumeInterface
{
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var MessageHandlerConsumeInterface|null stack of middleware
     */
    private ?MessageHandlerConsumeInterface $stack = null;

    /**
     * @param Closure[] $middlewares Middlewares.
     * @param MessageHandlerConsumeInterface $finishHandler Fallback handler
     * @param EventDispatcherInterface|null $dispatcher Event dispatcher to use for triggering before/after middleware
     * events.
     */
    public function __construct(
        private array $middlewares,
        private MessageHandlerConsumeInterface $finishHandler,
        private ?EventDispatcherInterface $dispatcher = null,
    ) {
    }

    public function handleConsume(ConsumeRequest $request): ConsumeRequest
    {
        if ($this->stack === null) {
            $this->build();
        }

        /** @psalm-suppress PossiblyNullReference */
        return $this->stack->handleConsume($request);
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
    private function wrap(Closure $middlewareFactory, MessageHandlerConsumeInterface $handler): MessageHandlerConsumeInterface
    {
        return new class ($middlewareFactory, $handler, $this->dispatcher) implements MessageHandlerConsumeInterface {
            private ?MiddlewareConsumeInterface $middleware = null;

            public function __construct(
                private Closure $middlewareFactory,
                private MessageHandlerConsumeInterface $handler,
                private ?EventDispatcherInterface $dispatcher
            ) {
            }

            public function handleConsume(ConsumeRequest $request): ConsumeRequest
            {
                if ($this->middleware === null) {
                    $this->middleware = ($this->middlewareFactory)();
                }

                $this->dispatcher?->dispatch(new BeforePushMiddleware($this->middleware, $request));

                try {
                    return $request = $this->middleware->processConsume($request, $this->handler);
                } finally {
                    $this->dispatcher?->dispatch(new AfterPushMiddleware($this->middleware, $request));
                }
            }
        };
    }
}
