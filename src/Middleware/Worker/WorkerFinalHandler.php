<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

use Psr\Log\LoggerInterface;
use Throwable;
use Yiisoft\Queue\Exception\MessageFailureException;
use Yiisoft\Queue\Message\Handler\HandlerResolver;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Middleware\Consume\ConsumeFinalHandler;
use Yiisoft\Queue\Middleware\Consume\ConsumeMiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureFinalHandler;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareDispatcher;

final class WorkerFinalHandler implements WorkerHandlerInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ConsumeMiddlewareDispatcher $consume,
        private readonly FailureMiddlewareDispatcher $failure,
        private readonly HandlerResolver $resolver,
    ) {}

    public function handleWorker(WorkerRequest $request): WorkerRequest
    {
        $message = $request->getMessage();
        $id = IdEnvelope::fromMessage($message)->getId();
        $id === null
            ? $this->logger->info('Processing message without ID.')
            : $this->logger->info('Processing message #{message}.', ['message' => $id]);

        // Resolution errors are deliberately outside failure handling.
        $handler = $this->resolver->resolve($message->getType());
        try {
            $consumeRequest = new ConsumeRequest($message, $request->getQueueName());
            $result = $this->consume->dispatch($consumeRequest, new ConsumeFinalHandler($handler->handle(...)));
            return $request->withMessage($result->getMessage());
        } catch (Throwable $exception) {
            $failureRequest = new FailureHandlingRequest(
                $message,
                $exception,
                $request->getQueueName(),
                $request->getRetry(),
            );
            try {
                $result = $this->failure->dispatch($failureRequest, new FailureFinalHandler());
                $this->logger->info($exception->getMessage());
                return $request->withMessage($result->getMessage());
            } catch (Throwable $failureException) {
                $failureException = new MessageFailureException($message, $failureException);
                $this->logger->error($failureException->getMessage());
                throw $failureException;
            }
        }
    }
}
