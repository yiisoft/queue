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

    public function getHandler(): string
    {
        return $this->handler;
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
