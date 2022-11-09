<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;
use Yiisoft\Definitions\Exception\NotInstantiableClassException;
use Yiisoft\Factory\Factory;
use Yiisoft\Factory\NotFoundException;
use Yiisoft\Injector\Injector;
use Yiisoft\Yii\Queue\Middleware\CallableFactory;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy\FailureStrategyInterface;
use Yiisoft\Yii\Queue\Middleware\InvalidMiddlewareDefinitionException;

final class FailureStrategyFactory
{
    public function __construct(
        private ContainerInterface $container,
        private CallableFactory $callableFactory,
        private Factory $factory,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function create(callable|array|string|FailureStrategyInterface $definition): FailureStrategyInterface
    {
        if ($definition instanceof FailureStrategyInterface) {
            return $definition;
        }

        if (is_string($definition) && is_subclass_of($definition, FailureStrategyInterface::class)) {
            /** @var FailureStrategyInterface */
            return $this->container->get($definition);
        }

        try {
            return $this->factory->create($definition);
        } catch (NotFoundException|NotInstantiableClassException) {
            $callable = $this->callableFactory->create($definition);

            return $this->wrapCallable($callable);
        }
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
