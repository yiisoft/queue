<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

abstract class Message implements MessageInterface
{
    /**
     * @psalm-var array<string, mixed>
     */
    private array $meta = [];

    final public function getMeta(): array
    {
        return $this->meta;
    }

    final public function withMeta(array $meta): static
    {
        $new = clone $this;
        $new->meta = $meta;
        return $new;
    }
}
