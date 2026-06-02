<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

abstract class Message implements MessageInterface
{
    /**
     * @psalm-var array<string, mixed>
     */
    private array $metadata = [];

    final public function getMetadata(): array
    {
        return $this->metadata;
    }

    final public function withMetadata(array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
//        $new = clone $this;
//        $new->metadata = $metadata;
//        return $new;
    }
}
