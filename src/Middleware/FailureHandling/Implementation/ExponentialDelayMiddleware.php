<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling\Implementation;

use InvalidArgumentException;
use Yiisoft\Queue\Message\DelayEnvelope;
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

/** Resends failures with exponentially increasing adapter-supported delay. */
final class ExponentialDelayMiddleware implements FailureMiddlewareInterface
{
    public const META_KEY_ATTEMPTS = 'failure-strategy-exponential-delay-attempts';
    public const META_KEY_DELAY = 'failure-strategy-exponential-delay-delay';

    public function __construct(
        private readonly string $id,
        private readonly int $maxAttempts,
        private readonly float $delayInitial,
        private readonly float $delayMaximum,
        private readonly float $exponent,
        private readonly ?QueueProducerInterface $queue = null,
        private readonly ?QueueProducerProviderInterface $producerProvider = null,
    ) {
        if ($maxAttempts <= 0) {
            throw new InvalidArgumentException("maxAttempts parameter must be a positive integer, $this->maxAttempts given.");
        }
        if ($delayInitial <= 0) {
            throw new InvalidArgumentException("delayInitial parameter must be a positive float, $this->delayInitial given.");
        }
        if ($delayMaximum < $delayInitial) {
            throw new InvalidArgumentException("delayMaximum parameter must not be less then delayInitial, $this->delayMaximum given.");
        }
        if ($exponent <= 0) {
            throw new InvalidArgumentException("exponent parameter must not be zero or less, $this->exponent given.");
        }
    }

    public function processFailure(FailureHandlingRequest $request, FailureHandlerInterface $handler): FailureHandlingRequest
    {
        $message = $request->getMessage();
        if (!$this->suites($message)) {
            return $handler->handleFailure($request);
        }
        $failure = new FailureEnvelope($message, $this->createNewMeta($message));
        $result = $this->producer($request)->push(new DelayEnvelope($failure, $this->getDelay($failure)));
        return $request->withMessage($result);
    }

    private function producer(FailureHandlingRequest $request): QueueProducerInterface
    {
        if ($this->queue !== null) {
            return $this->queue;
        }
        if ($request->getRetryProducer() !== null) {
            return $request->getRetryProducer();
        }
        if ($this->producerProvider === null) {
            throw new InvalidQueueConfigException(sprintf('Cannot retry queue "%s": configure a producer target or QueueProducerProviderInterface.', $request->getQueueName()));
        }
        try {
            return $this->producerProvider->getProducer($request->getQueueName());
        } catch (Throwable $exception) {
            throw new InvalidQueueConfigException(sprintf('Cannot retry queue "%s": no producer capability is available.', $request->getQueueName()), previous: $exception);
        }
    }

    private function suites(MessageInterface $message): bool
    {
        return $this->maxAttempts > $this->getAttempts($message);
    }

    private function createNewMeta(MessageInterface $message): array
    {
        return [self::META_KEY_DELAY . "-$this->id" => $this->getDelay($message), self::META_KEY_ATTEMPTS . "-$this->id" => $this->getAttempts($message) + 1];
    }

    private function getAttempts(MessageInterface $message): int
    {
        return (int) FailureEnvelope::fromMessage($message)->getFailureMetaValue(self::META_KEY_ATTEMPTS . "-$this->id", 0);
    }

    private function getDelay(MessageInterface $message): float
    {
        $original = (float) FailureEnvelope::fromMessage($message)->getFailureMetaValue(self::META_KEY_DELAY . "-$this->id", 0);
        return min(($original <= 0 ? $this->delayInitial : $original) * $this->exponent, $this->delayMaximum);
    }
}
