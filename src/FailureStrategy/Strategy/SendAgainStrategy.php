<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy\Strategy;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\FailureStrategy\Dispatcher\PipelineInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\PayloadFactory;
use Yiisoft\Yii\Queue\Queue;

final class SendAgainStrategy implements FailureStrategyInterface
{
    public const META_KEY_RESEND = 'failure-strategy-resend-attempts';

    private string $id;
    private int $maxAttempts;
    private Queue $queue;
    private PayloadFactory $factory;

    public function __construct(string $id, int $maxAttempts, Queue $queue, PayloadFactory $factory)
    {
        if ($maxAttempts < 1) {
            throw new InvalidArgumentException('maxAttempts parameter must be a positive integer');
        }

        $this->id = $id;
        $this->maxAttempts = $maxAttempts;
        $this->queue = $queue;
        $this->factory = $factory;
    }

    public function handle(MessageInterface $message, ?PipelineInterface $pipeline): bool
    {
        if ($this->suites($message)) {
            $this->queue->push($this->factory->createPayload($message, $this->createMeta($message)));

            return true;
        }

        return $pipeline === null ? false : $pipeline->handle($message);
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
        $result = $message->getPayloadMeta()[$this->getMetaKey()] ?? 0;
        if ($result < 0) {
            $result = 0;
        }

        return (int) $result;
    }
}
