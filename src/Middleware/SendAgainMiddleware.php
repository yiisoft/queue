<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

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
     * @param string $id A unique id to differentiate two and more objects of this class
     * @param int $maxAttempts Maximum attempts count for this strategy with the given $id before it will give up
     * @param QueueInterface|null $queue
     */
    public function __construct(
        private string $id,
        private int $maxAttempts,
        private ?QueueInterface $queue = null,
    ) {
        if ($maxAttempts < 1) {
            throw new InvalidArgumentException("maxAttempts parameter must be a positive integer, $this->maxAttempts given.");
        }
    }

    public function processFailure(
        FailureHandlingRequest $request,
        MessageFailureHandlerInterface $handler
    ): FailureHandlingRequest {
        $message = $request->getMessage();
        if ($this->suites($message)) {
            $envelope = new FailureEnvelope($message, $this->createMeta($message));
            $envelope = ($this->queue ?? $request->getQueue())->push($envelope);

            return $request->withMessage($envelope)
                ->withQueue($this->queue ?? $request->getQueue());
        }

        return $handler->handleFailure($request);
    }

    private function suites(MessageInterface $message): bool
    {
        return $this->getAttempts($message) < $this->maxAttempts;
    }

    private function createMeta(MessageInterface $message): array
    {
        $metadata = $message->getMetadata();
        $metadata[$this->getMetaKey()] = $this->getAttempts($message) + 1;

        return $metadata;
    }

    private function getAttempts(MessageInterface $message): int
    {
        $result = $message->getMetadata()[$this->getMetaKey()] ?? 0;
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
