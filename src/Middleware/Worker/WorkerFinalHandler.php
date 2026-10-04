<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Middleware\Consume\ConsumeFinalHandler;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;

final class WorkerFinalHandler implements WorkerHandlerInterface
{
    public function __construct(
        private readonly HandlerResolver $handlerResolver,
        private readonly ConsumeMiddlewareDispatcher $consumeMiddlewareDispatcher,
    ) {}

    public function handleWorker(WorkerRequest $request): WorkerRequest
    {
        $handler = $this->handlerResolver->resolve($request->getMessage()->getType());
        $consumeRequest = new ConsumeRequest($request->getMessage(), $request->getQueueName());
        $this->consumeMiddlewareDispatcher->dispatch($consumeRequest, new ConsumeFinalHandler($handler->handle(...)));

        return $request;
    }
}
