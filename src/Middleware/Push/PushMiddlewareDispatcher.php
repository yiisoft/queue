<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Closure;
use Yiisoft\Queue\Message\MessageInterface;

/**
 * @internal Used internally by {@see SyncQueueProducer} and {@see AsyncQueueProducer}.
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
     * @param PushHandlerInterface $finalHandler Handler invoked after all middlewares are processed.
     */
    public function __construct(
        private readonly PushMiddlewareFactoryInterface $middlewareFactory,
        private readonly array $middlewareDefinitions,
        private readonly PushHandlerInterface $finalHandler,
    ) {}

    /**
     * Dispatch message through middleware to get response.
     *
     * @param MessageInterface $message Message to pass to middleware.
     */
    public function dispatch(MessageInterface $message): MessageInterface
    {
        $this->stack ??= new PushMiddlewareStack($this->buildMiddlewares(), $this->finalHandler);

        return $this->stack->handlePush($message);
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
