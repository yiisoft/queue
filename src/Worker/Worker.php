<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Worker;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Yiisoft\Queue\Exception\JobFailureException;
use Yiisoft\Queue\Message\EnvelopeInterface;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\ConsumeFinalHandler;
use Yiisoft\Queue\Middleware\FailureFinalHandler;
use Yiisoft\Queue\Middleware\MiddlewareDispatcher;
use Yiisoft\Queue\Middleware\Request;
use Yiisoft\Queue\QueueInterface;

final class Worker implements WorkerInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private EventDispatcherInterface $eventDispatcher,
        private MiddlewareDispatcher $consumeMiddlewareDispatcher,
        private MiddlewareDispatcher $failureMiddlewareDispatcher,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function process(MessageInterface $message, QueueInterface $queue): MessageInterface
    {
        $this->logger->info(
            'Processing message #{message}.',
            ['message' => $message->getMetadata()[IdEnvelope::MESSAGE_ID_KEY] ?? 'null']
        );

        $request = new Request($message, $queue);

        $closure = function (object $message): mixed {
            $message = $message instanceof EnvelopeInterface ? $message->getMessage() : $message;
            return $this->eventDispatcher->dispatch($message);
        };

        try {
            $result = $this->consumeMiddlewareDispatcher->dispatch($request, new ConsumeFinalHandler($closure));
            return $result->getMessage();
        } catch (Throwable $exception) {
            try {
                $result = $this->failureMiddlewareDispatcher->dispatch($request, new FailureFinalHandler($exception));
                $this->logger->info($exception);

                return $result->getMessage();
            } catch (Throwable $exception) {
                $exception = new JobFailureException($message, $exception);
                $this->logger->error($exception);
                throw $exception;
            }
        }
    }
}
