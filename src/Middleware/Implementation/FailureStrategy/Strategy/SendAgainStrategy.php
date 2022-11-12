<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy;

use InvalidArgumentException;
use Throwable;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\QueueInterface;

/**
 * Failure strategy which resends the given message to a queue with an exponentially increasing delay.
 * The delay **must** be implemented by the used {@see AdapterInterface} implementation.
 */
final class SendAgainStrategy implements FailureStrategyInterface
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
            throw new InvalidArgumentException('maxAttempts parameter must be a positive integer.');
        }
    }

    public function handle(ConsumeRequest $request, Throwable $exception, PipelineInterface $pipeline): ConsumeRequest
    {
        $message = $request->getMessage();
        if ($this->suites($message)) {
            $message = new Message(
                handlerName: $message->getHandlerName(),
                data: $message->getData(),
                metadata: $this->createMeta($message),
                id: $message->getId(),
            );
            $this->queue?->push($message) ?? $request->getQueue()->push($message);

            return $request->withMessage($message)->withQueue($this->queue ?? $request->getQueue());
        }

        return $pipeline->handle($request, $exception);
    }

    private function suites(MessageInterface $message): bool
    {
        return $this->getAttempts($message) < $this->maxAttempts;
    }

    private function getMetaKey(): string
    {
        return self::META_KEY_RESEND . "-$this->id";
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
}
