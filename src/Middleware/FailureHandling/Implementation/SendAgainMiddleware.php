<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling\Implementation;

use InvalidArgumentException;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\FailureHandling\FailureEnvelope;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;
use Yiisoft\Queue\Middleware\FailureHandling\MiddlewareFailureInterface;
use Yiisoft\Queue\QueueInterface;

/**
 * Failure strategy which resends the given message to a queue.
 */
final class SendAgainMiddleware implements MiddlewareFailureInterface
{
    public const META_KEY_RESEND = 'failure-strategy-resend-attempts';

    /**
     * @param string $id A unique id to differentiate two and more instances of this class
     * @param int $maxAttempts Maximum attempts count for this strategy with the given $id before it will give up
     * @param QueueInterface|null $targetQueue Messages will be sent to this queue if set.
     *        They will be resent to an original queue otherwise.
     */
    public function __construct(
        private readonly string $id,
        private readonly int $maxAttempts,
        private readonly ?QueueInterface $targetQueue = null,
    ) {
        if ($maxAttempts < 1) {
            throw new InvalidArgumentException("maxAttempts parameter must be a positive integer, $this->maxAttempts given.");
        }
    }

    public function processFailure(
        FailureHandlingRequest $request,
        MessageFailureHandlerInterface $handler,
    ): FailureHandlingRequest {
        $message = $request->getMessage();
        if ($this->suites($message)) {
            $envelope = new FailureEnvelope($message, $this->createMeta($message));
            $envelope = ($this->targetQueue ?? $request->getQueue())->push($envelope);

            return $request->withMessage($envelope)
                ->withQueue($this->targetQueue ?? $request->getQueue());
        }

        return $handler->handleFailure($request);
    }

    private function suites(MessageInterface $message): bool
    {
        return $this->getAttempts($message) < $this->maxAttempts;
    }

    private function createMeta(MessageInterface $message): array
    {
        return [$this->getMetaKey() => $this->getAttempts($message) + 1];
    }

    private function getAttempts(MessageInterface $message): int
    {
        $result = $message->getMetadata()[FailureEnvelope::FAILURE_META_KEY][$this->getMetaKey()] ?? 0;
        if ($result < 0) {
            $result = 0;
        }

        return (int) $result;
    }

    private function getMetaKey(): string
    {
        return self::META_KEY_RESEND . "-$this->id";
    }
}
