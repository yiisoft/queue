<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy;

use InvalidArgumentException;
use Throwable;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Implementation\DelayMiddlewareInterface;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\Queue;

final class ExponentialDelayStrategy implements FailureStrategyInterface
{
    public const META_KEY_ATTEMPTS = 'failure-strategy-exponential-delay-attempts';
    public const META_KEY_DELAY = 'failure-strategy-exponential-delay-delay';

    /**
     * @param int $maxAttempts Maximum attempts count before this strategy will give up
     * @param float $delayInitial The first delay period
     * @param float $delayMaximum The maximum delay period
     * @param float $exponent The multiplication of delay increasing
     * @param Queue $queue
     * @param DelayMiddlewareInterface $delayMiddleware
     */
    public function __construct(
        private int $maxAttempts,
        private float $delayInitial,
        private float $delayMaximum,
        private float $exponent,
        private Queue $queue,
        private DelayMiddlewareInterface $delayMiddleware,
    ) {
        if ($maxAttempts <= 0) {
            throw new InvalidArgumentException('maxAttempts parameter must be a positive integer');
        }

        if ($delayInitial < 0) {
            throw new InvalidArgumentException('delayInitial parameter must not be zero or less');
        }

        if ($delayMaximum <= 0) {
            throw new InvalidArgumentException('delayMaximum parameter must not be zero or less');
        }

        if ($delayMaximum < $delayInitial) {
            throw new InvalidArgumentException('delayMaximum parameter must not be less then delayInitial');
        }

        if ($exponent <= 0) {
            throw new InvalidArgumentException('exponent parameter must not be zero or less');
        }
    }

    private function suites(MessageInterface $message): bool
    {
        return $this->maxAttempts > $this->getAttempts($message);
    }

    public function handle(ConsumeRequest $request, Throwable $exception, PipelineInterface $pipeline): ConsumeRequest
    {
        $message = $request->getMessage();
        if ($this->suites($message)) {
            $messageNew = new Message(
                handlerName: $message->getHandlerName(),
                data: $message->getData(),
                metadata: $this->formNewMeta($message),
                id: $message->getId(),
            );
            $this->queue->push($messageNew, $this->delayMiddleware->withDelay($this->getDelay($message)));

            return $request;
        }

        return $pipeline->handle($request, $exception);
    }

    private function formNewMeta(MessageInterface $message): array
    {
        $meta = $message->getMetadata();
        $meta[self::META_KEY_DELAY] = $this->getDelay($message);
        $meta[self::META_KEY_ATTEMPTS] = $this->getAttempts($message) + 1;

        return $meta;
    }

    private function getAttempts(MessageInterface $message): int
    {
        return $message->getMetadata()[self::META_KEY_ATTEMPTS] ?? 0;
    }

    private function getDelay(MessageInterface $message): float
    {
        $meta = $message->getMetadata();
        if (isset($meta[self::META_KEY_DELAY])) {
            $delayOriginal = (float) $meta[self::META_KEY_DELAY];
            if ($delayOriginal === 0.0) {
                $delayOriginal = 0.5;
            }
        } else {
            $delayOriginal = $this->delayInitial;
        }

        $result = $delayOriginal * $this->exponent;

        return min($result, $this->delayMaximum);
    }
}
