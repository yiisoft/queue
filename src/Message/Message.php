<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

final class Message implements MessageInterface
{
    /**
     * @param mixed $data Message data, encodable by a queue adapter
     * @param array $metadata Message metadata, encodable by a queue adapter
     * @param string|null $id Message id
     */
    public function __construct(
        private string $handlerName,
        private mixed $data,
        private array $metadata = [],
    ) {
    }

    public function getHandlerName(): string
    {
        return $this->handlerName;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function withMetadata(array $metadata): self
    {
        $instance = clone $this;
        $instance->metadata = $metadata;

        return $instance;
    }
}
