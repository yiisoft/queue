<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher;

use Psr\Container\ContainerInterface;
use Throwable;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy\FailureStrategyInterface;
use Yiisoft\Yii\Queue\Middleware\InvalidMiddlewareDefinitionException;

final class FailureStrategyFactory
{
    public function __construct(
        private ContainerInterface $container,
        private CallableFactory $callableFactory,
    ) {
    }

    public function create(callable|array|string|FailureStrategyInterface $definition)
    {
        if ($definition instanceof FailureStrategyInterface) {
            return $definition;
        }

        if (is_string($definition) && is_subclass_of($definition, FailureStrategyInterface::class)) {
            /** @var FailureStrategyInterface */
            return $this->container->get($definition);
        }

        $callable = $this->callableFactory->create($definition);

        return $this->wrapCallable($callable);
    }

    private function wrapCallable(callable $callback): FailureStrategyInterface
    {
        return new class ($callback, $this->container) implements FailureStrategyInterface {
            private ContainerInterface $container;
            private $callback;

            public function __construct(callable $callback, ContainerInterface $container)
            {
                $this->callback = $callback;
                $this->container = $container;
            }

            public function handle(
                ConsumeRequest $request,
                Throwable $exception,
                PipelineInterface $pipeline
            ): ConsumeRequest {
                $response = (new Injector($this->container))->invoke($this->callback, [$request, $exception, $pipeline]);
                if ($response instanceof ConsumeRequest) {
                    return $response;
                }

                if ($response instanceof FailureStrategyInterface) {
                    return $response->handle($request, $exception, $pipeline);
                }

                throw new InvalidMiddlewareDefinitionException($this->callback);
            }
        };
    }
}
