<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

interface MessageInterface
{
    public static function fromData(string $type, mixed $data): self;

    /**
     * Returns message type.
     */
    public function getType(): string;

    /**
     * Returns payload data.
     */
    public function getData(): mixed;

    /**
     * Returns message metadata: timings, attempts count, metrics, etc. Keys are always strings.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;

    /**
     * Returns a new instance with the given message metadata.
     *
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): static;
}
