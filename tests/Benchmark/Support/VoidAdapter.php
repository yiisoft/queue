<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Benchmark\Support;

use BackedEnum;
use InvalidArgumentException;
use RuntimeException;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageSerializerInterface;

final class VoidAdapter implements AdapterInterface
{
    /**
     * @var string A serialized message
     */
    public string $message;

    public function __construct(private MessageSerializerInterface $serializer)
    {
    }

    public function runExisting(callable $handlerCallback): void
    {
        $handlerCallback($this->serializer->unserialize($this->message));
    }

    public function status(int|string $id): JobStatus
    {
        throw new InvalidArgumentException();
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

    public function withChannel(string|BackedEnum $channel): AdapterInterface
    {
        throw new RuntimeException('Method is not implemented');
    }

    public function getChannelName(): string
    {
        throw new RuntimeException('Method is not implemented');
    }
}
