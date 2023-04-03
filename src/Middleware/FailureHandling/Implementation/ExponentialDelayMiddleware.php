<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\FailureHandling\Implementation;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\MiddlewareFailureInterface;
use Yiisoft\Yii\Queue\Middleware\Push\Implementation\DelayMiddlewareInterface;
use Yiisoft\Yii\Queue\QueueInterface;

/**
 * Failure strategy which resends the given message to a queue with an exponentially increasing delay.
 * The delay mechanism **must** be implemented by the used {@see AdapterInterface} implementation.
 */
final class ExponentialDelayMiddleware implements MiddlewareFailureInterface
{
    public const META_KEY_ATTEMPTS = 'failure-strategy-exponential-delay-attempts';
    public const META_KEY_DELAY = 'failure-strategy-exponential-delay-delay';

    /**
     * @param string $id A unique id to differentiate two and more objects of this class
     * @param int $maxAttempts Maximum attempts count for this strategy with the given $id before it will give up
     * @param float $delayInitial The first delay period
     * @param float $delayMaximum The maximum delay period
     * @param float $exponent Message handling delay will be increased by this multiplication each time it fails
     * @param DelayMiddlewareInterface $delayMiddleware
     * @param QueueInterface|null $queue
     */
    public function __construct(
        private string $id,
        private int $maxAttempts,
        private float $delayInitial,
        private float $delayMaximum,
        private float $exponent,
        private DelayMiddlewareInterface $delayMiddleware,
        private ?QueueInterface $queue = null,
    ) {
        if ($maxAttempts <= 0) {
            throw new InvalidArgumentException("maxAttempts parameter must be a positive integer, $this->maxAttempts given.");
        }

        if ($delayInitial <= 0) {
            throw new InvalidArgumentException("delayInitial parameter must be a positive float, $this->delayInitial given.");
        }

        if ($delayMaximum < $delayInitial) {
            throw new InvalidArgumentException("delayMaximum parameter must not be less then delayInitial, , $this->delayMaximum given.");
        }

        if ($exponent <= 0) {
            throw new InvalidArgumentException("exponent parameter must not be zero or less, $this->exponent given.");
        }
    }

    public function processFailure(
        FailureHandlingRequest $request,
        MessageFailureHandlerInterface $handler
    ): FailureHandlingRequest {
        $message = $request->getMessage();
        if ($this->suites($message)) {
            $messageNew = new Message(
                handlerName: $message->getHandlerName(),
                data:        $message->getData(),
                metadata:    $this->formNewMeta($message),
                id:          $message->getId(),
            );
            ($this->queue ?? $request->getQueue())->push(
                $messageNew,
                $this->delayMiddleware->withDelay($this->getDelay($message))
            );

            return $request->withMessage($messageNew);
        }

        return $handler->handleFailure($request);
    }

    private function suites(MessageInterface $message): bool
    {
        return $this->maxAttempts > $this->getAttempts($message);
    }

    private function formNewMeta(MessageInterface $message): array
    {
        $meta = $message->getMetadata();
        $meta[self::META_KEY_DELAY . "-$this->id"] = $this->getDelay($message);
        $meta[self::META_KEY_ATTEMPTS . "-$this->id"] = $this->getAttempts($message) + 1;

        return $meta;
    }

    private function getAttempts(MessageInterface $message): int
    {
        return $message->getMetadata()[self::META_KEY_ATTEMPTS . "-$this->id"] ?? 0;
    }

    private function getDelay(MessageInterface $message): float
    {
        $meta = $message->getMetadata();
        $key = self::META_KEY_DELAY . "-$this->id";

        $delayOriginal = (float) ($meta[$key] ?? 0 ?: $this->delayInitial);
        $result = $delayOriginal * $this->exponent;

        return min($result, $this->delayMaximum);
    }
}
