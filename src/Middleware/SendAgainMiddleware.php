<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

use InvalidArgumentException;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\FailureEnvelope;
use Yiisoft\Queue\QueueInterface;

/**
 * Failure strategy which resends the given message to a queue.
 */
final class SendAgainMiddleware implements MiddlewareInterface
{
    public const META_KEY_RESEND = 'failure-strategy-resend-attempts';

    /**
     * @param string $id A unique id to differentiate two and more objects of this class
     * @param int $maxAttempts Maximum attempts count for this strategy with the given $id before it will give up
     */
    public function __construct(
        private string $id,
        private int $maxAttempts,
        private QueueInterface $queue
    ) {
        if ($maxAttempts < 1) {
            throw new InvalidArgumentException("maxAttempts parameter must be a positive integer, $this->maxAttempts given.");
        }
    }

    public function process(Request $request, MessageHandlerInterface $handler): Request {
        $message = $request->getMessage();
        if ($this->suites($message)) {
            $envelope = new FailureEnvelope($message, $this->createMeta($message));
            $envelope = $this->queue->push($envelope);

            $request1 = $request->withMessage($envelope);
            return $request1->withQueue($this->queue);
        }

        return $handler->handle($request);
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
