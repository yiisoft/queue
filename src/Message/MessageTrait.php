<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

trait MessageTrait
{
    private mixed $data = [];
    private array $metadata = [];

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
