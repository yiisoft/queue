<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling\Implementation;

use InvalidArgumentException;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlerInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareInterface;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\QueueProducerProviderInterface;
use Yiisoft\Queue\QueueProducerInterface;
use Throwable;

use function sprintf;

/** Failure strategy which resends a message through a producer capability. */
final class SendAgainMiddleware implements FailureMiddlewareInterface
{
    public const META_KEY_RESEND = 'failure-strategy-resend-attempts';

    public function __construct(
        private readonly string $id,
        private readonly int $maxAttempts,
        private readonly ?QueueProducerInterface $targetQueue = null,
        private readonly ?QueueProducerProviderInterface $producerProvider = null,
    ) {
        if ($maxAttempts < 1) {
            throw new InvalidArgumentException("maxAttempts parameter must be a positive integer, $this->maxAttempts given.");
        }
    }

    public function processFailure(FailureHandlingRequest $request, FailureHandlerInterface $handler): FailureHandlingRequest
    {
        $message = $request->getMessage();
        if (!$this->suits($message)) {
            return $handler->handleFailure($request);
        }
        $envelope = new FailureEnvelope($message, [$this->getMetaKey() => $this->getAttempts($message) + 1]);
        $producer = $this->targetQueue ?? $request->getRetryProducer() ?? $this->sourceProducer($request);
        $envelope = $producer->push($envelope);
        return $request->withMessage($envelope);
    }

    private function sourceProducer(FailureHandlingRequest $request): QueueProducerInterface
    {
        if ($this->producerProvider === null) {
            throw new InvalidQueueConfigException(sprintf('Cannot retry queue "%s": configure a producer target or QueueProducerProviderInterface.', $request->getQueueName()));
        }
        try {
            return $this->producerProvider->getProducer($request->getQueueName());
        } catch (Throwable $exception) {
            throw new InvalidQueueConfigException(sprintf('Cannot retry queue "%s": no producer capability is available.', $request->getQueueName()), previous: $exception);
        }
    }

    private function suits(MessageInterface $message): bool
    {
        return $this->getAttempts($message) < $this->maxAttempts;
    }

    private function getAttempts(MessageInterface $message): int
    {
        return max(0, (int) FailureEnvelope::fromMessage($message)->getFailureMetaValue($this->getMetaKey(), 0));
    }

    private function getMetaKey(): string
    {
        return self::META_KEY_RESEND . "-$this->id";
    }
}
