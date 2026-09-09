<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Worker;

use Closure;
use LogicException;
use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Worker\WorkerFinalHandler;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareFactoryInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerMiddlewareInterface;
use Yiisoft\Queue\Middleware\Worker\WorkerRequest;

final class Worker
{
    private readonly WorkerMiddlewareDispatcher $workerMiddlewareDispatcher;

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
    }

    /** @param Closure(MessageInterface): MessageInterface $retry */
    public function process(MessageInterface $message, string $queueName, ?Closure $retry = null): MessageInterface
    {
        $request = new WorkerRequest($message, $queueName, $retry);
        $final = new WorkerFinalHandler(
            $this->logger,
            $this->consumeMiddlewareDispatcher,
            $this->failureMiddlewareDispatcher,
            $this->handlerResolver,
        );

        return $this->workerMiddlewareDispatcher->dispatch($request, $final)->getMessage();
    }
}
