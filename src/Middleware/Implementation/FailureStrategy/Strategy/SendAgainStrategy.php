<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Strategy;

use InvalidArgumentException;
use Throwable;
use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\Consume\ConsumeRequest;
use Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\Queue;

final class SendAgainStrategy implements FailureStrategyInterface
{
    public const META_KEY_RESEND = 'failure-strategy-resend-attempts';

    public function __construct(
        private string $id,
        private int $maxAttempts,
        private Queue $queue,
    ) {
        if ($maxAttempts < 1) {
            throw new InvalidArgumentException('maxAttempts parameter must be a positive integer');
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
            $this->queue->push($message);

            return $request;
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
        return [$this->getMetaKey() => $this->getAttempts($message) + 1];
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
