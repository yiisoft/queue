<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Worker;

use Closure;
use LogicException;
use Psr\Log\LoggerInterface;
use Throwable;
use Yiisoft\Queue\Exception\MessageFailureException;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeFinalHandler;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureFinalHandler;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Worker\WorkerFinalHandler;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;

final class Worker
{
    private readonly WorkerMiddlewareDispatcher $workerMiddlewareDispatcher;
    private readonly WorkerFinalHandler $workerFinalHandler;
    private readonly bool $hasWorkerMiddlewares;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConsumeMiddlewareDispatcher $consumeMiddlewareDispatcher,
        private readonly FailureMiddlewareDispatcher $failureMiddlewareDispatcher,
        private readonly HandlerResolver $handlerResolver,
        ?WorkerMiddlewareDispatcher $workerMiddlewareDispatcher = null,
    ) {
        $this->workerMiddlewareDispatcher = $workerMiddlewareDispatcher ?? new WorkerMiddlewareDispatcher(
            new class implements WorkerMiddlewareFactoryInterface {
                public function createWorkerMiddleware(mixed $definition): WorkerMiddlewareInterface
                {
                    throw new LogicException('The empty worker middleware dispatcher cannot create middleware.');
                }
            },
        );
        $this->workerFinalHandler = new WorkerFinalHandler(
            $this->logger,
            $this->consumeMiddlewareDispatcher,
            $this->failureMiddlewareDispatcher,
            $this->handlerResolver,
        );
        $this->hasWorkerMiddlewares = $this->workerMiddlewareDispatcher->hasMiddlewares();
    }

    /** @param Closure(MessageInterface): MessageInterface $retry */
    public function process(MessageInterface $message, string $queueName, ?Closure $retry = null): MessageInterface
    {
        if (!$this->hasWorkerMiddlewares) {
            $id = IdEnvelope::fromMessage($message)->getId();
            $id === null
                ? $this->logger->info('Processing message without ID.')
                : $this->logger->info('Processing message #{message}.', ['message' => $id]);

            try {
                $handler = $this->handlerResolver->resolve($message->getType());
                $result = $this->consumeMiddlewareDispatcher->dispatch(
                    new ConsumeRequest($message, $queueName),
                    new ConsumeFinalHandler($handler->handle(...)),
                );
                return $result->getMessage();
            } catch (Throwable $exception) {
                $failureRequest = new FailureHandlingRequest($message, $exception, $queueName, $retry);
                try {
                    $result = $this->failureMiddlewareDispatcher->dispatch($failureRequest, new FailureFinalHandler());
                    $this->logger->info($exception->getMessage());
                    return $result->getMessage();
                } catch (Throwable $failureException) {
                    $failureException = new MessageFailureException($message, $failureException);
                    $this->logger->error($failureException->getMessage());
                    throw $failureException;
                }
            }
        }

        return $this->workerMiddlewareDispatcher
            ->dispatch(new WorkerRequest($message, $queueName, $retry), $this->workerFinalHandler)
            ->getMessage();
    }
}
