<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling\Implementation;

use Closure;
use InvalidArgumentException;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlerInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureMiddlewareInterface;
use Yiisoft\Queue\Provider\InvalidQueueConfigException;
use Yiisoft\Queue\Provider\QueueProducerProviderInterface;
use Throwable;

use function sprintf;

/** Failure strategy which resends a message through a producer capability. */
final class SendAgainMiddleware implements FailureMiddlewareInterface
{
    public const META_KEY_RESEND = 'failure-strategy-resend-attempts';

    /**
     * @param Closure(MessageInterface): MessageInterface $targetQueue
     */
    public function __construct(
        private readonly string $id,
        private readonly int $maxAttempts,
        private readonly ?Closure $targetQueue = null,
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
        $retry = $this->targetQueue ?? $request->getRetry() ?? $this->sourceProducer($request);
        return $request->withMessage($retry($envelope));
    }

    /** @return Closure(MessageInterface): MessageInterface */
    private function sourceProducer(FailureHandlingRequest $request): Closure
    {
        if ($this->producerProvider === null) {
            throw new InvalidQueueConfigException(sprintf('Cannot retry queue "%s": configure a producer target or QueueProducerProviderInterface.', $request->getQueueName()));
        }
        try {
            $producer = $this->producerProvider->getProducer($request->getQueueName());
            return static fn(MessageInterface $message): MessageInterface => $producer->push($message);
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
