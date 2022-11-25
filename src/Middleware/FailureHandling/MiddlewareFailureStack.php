<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\FailureHandling;

use Closure;

final class MiddlewareFailureStack implements MessageHandlerFailureInterface
{
    /**
     * Contains a stack of middleware wrapped in handlers.
     * Each handler points to the handler of middleware that will be processed next.
     *
     * @var MessageHandlerFailureInterface|null stack of middleware
     */
    private ?MessageHandlerFailureInterface $stack = null;

    /**
     * @param Closure[] $middlewares Middlewares.
     * @param MessageHandlerFailureInterface $finishHandler Fallback handler
     * events.
     */
    public function __construct(
        private array $middlewares,
        private MessageHandlerFailureInterface $finishHandler,
    ) {
    }

    public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest
    {
        if ($this->stack === null) {
            $this->build();
        }

        /** @psalm-suppress PossiblyNullReference */
        return $this->stack->handleFailure($request);
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
    private function wrap(Closure $middlewareFactory, MessageHandlerFailureInterface $handler): MessageHandlerFailureInterface
    {
        return new class ($middlewareFactory, $handler) implements MessageHandlerFailureInterface {
            private ?MiddlewareFailureInterface $middleware = null;

            public function __construct(
                private Closure $middlewareFactory,
                private MessageHandlerFailureInterface $handler,
            ) {
            }

            public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest
            {
                if ($this->middleware === null) {
                    $this->middleware = ($this->middlewareFactory)();
                }

                return $this->middleware->processFailure($request, $this->handler);
            }
        };
    }
}
