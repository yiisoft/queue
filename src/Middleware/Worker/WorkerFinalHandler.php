<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Worker;

use Closure;
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
        return $request->withMessage($this->process(
            $request->getMessage(),
            $request->getQueueName(),
            $request->getRetry(),
        ));
    }

    /** @param null|Closure(MessageInterface): MessageInterface $retry */
    public function process(MessageInterface $message, string $queueName, ?Closure $retry = null): MessageInterface
    {
        $id = IdEnvelope::fromMessage($message)->getId();
        $id === null
            ? $this->logger->info('Processing message without ID.')
            : $this->logger->info('Processing message #{message}.', ['message' => $id]);

        try {
            $handler = $this->resolver->resolve($message->getType());
            $consumeRequest = new ConsumeRequest($message, $queueName);
            $result = $this->consume->dispatch($consumeRequest, new ConsumeFinalHandler($handler->handle(...)));
            return $result->getMessage();
        } catch (Throwable $exception) {
            $failureRequest = new FailureHandlingRequest($message, $exception, $queueName, $retry);
            try {
                $result = $this->failure->dispatch($failureRequest, new FailureFinalHandler());
                $this->logger->info($exception->getMessage());
                return $result->getMessage();
            } catch (Throwable $failureException) {
                $failureException = new MessageFailureException($message, $failureException);
                $this->logger->error($failureException->getMessage());
                throw $failureException;
            }
        }
    }
}
