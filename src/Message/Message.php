<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * Base implementation of {@see MessageInterface} providing metadata storage.
 *
 * @psalm-import-type MessageMeta from MessageInterface
 */
abstract class Message implements MessageInterface
{
    /**
     * @psalm-var MessageMeta
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
