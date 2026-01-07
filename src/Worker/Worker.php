<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Worker;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use Yiisoft\Injector\Injector;
use Yiisoft\Queue\Exception\JobFailureException;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageHandlerInterface;
use Yiisoft\Queue\Middleware\CallableFactory;
use Yiisoft\Queue\Middleware\InvalidCallableConfigurationException;
use Yiisoft\Queue\Middleware\Consume\ConsumeFinalHandler;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Queue\Middleware\Consume\MessageHandlerConsumeInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureFinalHandler;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Message\IdEnvelope;

final class Worker implements WorkerInterface
{
    /** @var array<non-empty-string, callable|null> Cache of resolved handlers */
    private array $handlersCached = [];

    public function __construct(
        /** @var array<non-empty-string, array|callable|object|string|null> */
        private readonly array $handlers,
        private readonly LoggerInterface $logger,
        private readonly Injector $injector,
        private readonly ContainerInterface $container,
        private readonly ConsumeMiddlewareDispatcher $consumeMiddlewareDispatcher,
        private readonly FailureMiddlewareDispatcher $failureMiddlewareDispatcher,
        private readonly CallableFactory $callableFactory,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function process(MessageInterface $message, QueueInterface $queue): MessageInterface
    {
        $this->logger->info('Processing message #{message}.', ['message' => $message->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? 'null']);

        $name = $message->getHandlerName();
        try {
            $handler = $this->getHandler($name);
        } catch (InvalidCallableConfigurationException $exception) {
            throw new RuntimeException(sprintf('Queue handler with name "%s" does not exist', $name), 0, $exception);
        }

        if ($handler === null) {
            throw new RuntimeException(sprintf('Queue handler with name "%s" does not exist', $name));
        }

        $request = new ConsumeRequest($message, $queue);
        $closure = fn (MessageInterface $message): mixed => $this->injector->invoke($handler, [$message]);
        try {
            return $this->consumeMiddlewareDispatcher->dispatch($request, $this->createConsumeHandler($closure))->getMessage();
        } catch (Throwable $exception) {
            $request = new FailureHandlingRequest($request->getMessage(), $exception, $request->getQueue());

            try {
                $result = $this->failureMiddlewareDispatcher->dispatch($request, $this->createFailureHandler());
                $this->logger->info($exception->getMessage());

                return $result->getMessage();
            } catch (Throwable $exception) {
                $exception = new JobFailureException($message, $exception);
                $this->logger->error($exception->getMessage());
                throw $exception;
            }
        }
    }

    private function getHandler(string $name): ?callable
    {
        if ($name === '') {
            return null;
        }

        if (!array_key_exists($name, $this->handlersCached)) {
            $definition = $this->handlers[$name] ?? $name;

            if (is_string($definition) && $this->container->has($definition)) {
                $resolved = $this->container->get($definition);

                if ($resolved instanceof MessageHandlerInterface) {
                    $this->handlersCached[$name] = $resolved->handle(...);

                    return $this->handlersCached[$name];
                }
            }

            $this->handlersCached[$name] = $this->callableFactory->create($definition);
        }

        return $this->handlersCached[$name];
    }

    private function createConsumeHandler(Closure $handler): MessageHandlerConsumeInterface
    {
        return new ConsumeFinalHandler($handler);
    }

    private function createFailureHandler(): MessageFailureHandlerInterface
    {
        return new FailureFinalHandler();
    }
}
