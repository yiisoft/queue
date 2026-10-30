<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Benchmark\Support;

use RuntimeException;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\Serializer\MessageSerializer;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;

final class VoidAdapter implements AdapterInterface
{
    /**
     * @var string A serialized message
     */
    public string $message;

    public function __construct(private readonly MessageSerializer $serializer) {}

    public function runExisting(callable $handlerCallback): void
    {
        $handlerCallback($this->serializer->unserialize($this->message));
    }

    public function status(int|string $id): MessageStatus
    {
        return MessageStatus::NOT_FOUND;
    }

    public function hasStatusSupport(): bool
    {
        return false;
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $this->serializer->serialize($message);

        return new IdEnvelope($message, 1);
    }

    public function subscribe(callable $handlerCallback): void
    {
        throw new RuntimeException('Method is not implemented');
    }
}
