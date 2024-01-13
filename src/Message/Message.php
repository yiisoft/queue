<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

final class Message implements MessageInterface
{
    /**
     * @param class-string<MessageHandlerInterface> $handler Message handler class name
     * @param mixed $data Message data, encodable by a queue adapter
     * @param array $metadata Message metadata, encodable by a queue adapter
     * @param string|null $id Message id
     */
    public function __construct(
        private string $handler,
        private mixed $data,
        private array $metadata = [],
    ) {
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function withData(mixed $data): self
    {
        $new = clone $this;
        $new->data = $data;
        return $new;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function withMetadata(array $metadata): self
    {
        $new = clone $this;
        $new->metadata = $metadata;
        return $new;
    }
}
