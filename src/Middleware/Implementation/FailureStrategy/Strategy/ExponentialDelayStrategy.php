<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy;

use InvalidArgumentException;
use Throwable;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\PayloadFactory;
use Yiisoft\Yii\Queue\Queue;

final class ExponentialDelayStrategy implements FailureStrategyInterface
{
    public const META_KEY_ATTEMPTS = 'failure-strategy-exponential-delay-attempts';
    public const META_KEY_DELAY = 'failure-strategy-exponential-delay-delay';

    private int $maxAttempts;
    private float $delayInitial;
    private float $delayMaximum;
    private float $exponent;
    private Queue $queue;
    private PayloadFactory $factory;

    /**
     * ExponentialDelayStrategy constructor.
     *
     * @param int $maxAttempts Maximum attempts count before this strategy will give up
     * @param float $delayInitial
     * @param float $delayMaximum
     * @param float $exponent
     * @param PayloadFactory $factory
     * @param Queue $queue
     */
    public function __construct(
        int $maxAttempts,
        float $delayInitial,
        float $delayMaximum,
        float $exponent,
        PayloadFactory $factory,
        Queue $queue
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

        $this->maxAttempts = $maxAttempts;
        $this->delayInitial = $delayInitial;
        $this->delayMaximum = $delayMaximum;
        $this->exponent = $exponent;
        $this->factory = $factory;
        $this->queue = $queue;
    }

    private function suites(MessageInterface $message): bool
    {
        return $this->maxAttempts > $this->getAttempts($message->getMetadata());
    }

    public function handle(ConsumeRequest $request, Throwable $exception, ?PipelineInterface $pipeline): ConsumeRequest
    {
        $message = $request->getMessage();
        if ($this->suites($message)) {
            $message = new Message(
                handlerName: $message->getHandlerName(),
                data: $message->getData(),
                metadata: $this->formNewMeta($message->getMetadata()),
                id: $message->getId(),
            );
            $this->queue->push($message, /** TODO place delaying middleware here */);

            return $request;
        }

        if ($pipeline === null) {
            return $request;
        }

        return $pipeline->handle($request, $exception);
    }

    private function formNewMeta(array $meta): array
    {
        $meta[self::META_KEY_DELAY] = $this->getDelay($meta);
        $meta[self::META_KEY_ATTEMPTS] = $this->getAttempts($meta) + 1;

        return $meta;
    }

    private function getAttempts(array $meta): int
    {
        return $meta[self::META_KEY_ATTEMPTS] ?? 0;
    }

    private function getDelay(array $meta): float
    {
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
