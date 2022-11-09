<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

final class Message implements MessageInterface
{
    /**
     * @param string $handlerName
     * @param mixed $data Message data, encodable by a used adapter
     * @param mixed $metadata Message metadata
     * @param string|null $id Message id
     */
    public function __construct(
        private string $handlerName,
        private mixed $data,
        private array $metadata = [],
        private ?string $id = null
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

    public function setId(?string $id): void
    {
        $this->id = $id;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
