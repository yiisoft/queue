<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

final class Message implements MessageInterface
{
    /**
     * @param string $type A message type used to resolve the handler.
     * @param mixed $data Message data, encodable by a queue adapter
     * @param array $metadata Message metadata, encodable by a queue adapter
     */
    public function __construct(
        private readonly string $type,
        private readonly mixed $data,
        private array $metadata = [],
    ) {}

    public static function fromData(string $type, mixed $data, array $metadata = []): MessageInterface
    {
        return new self($type, $data, $metadata);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
