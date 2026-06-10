<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * Represents a queue message with a type identifier, payload data, and metadata.
 *
 * @psalm-type MessageData = scalar|null|array<scalar|null|array>
 * @psalm-type MessageMetadata = array<string, scalar|null|array<scalar|null|array>>
 */
interface MessageInterface
{
    /**
     * Creates a new message instance from the given type and payload data.
     *
     * @param string $type Message type.
     * @param bool|int|float|string|array|null $data Message payload data. Must contain only `null`, scalars (`bool`,
     * `int`, `float`, `string`), or arrays composed of the same types recursively.
     *
     * @psalm-param MessageData $data
     */
    public static function fromData(string $type, bool|int|float|string|array|null $data): self;

    /**
     * Returns message type.
     *
     * @return string Message type.
     */
    public function getType(): string;

    /**
     * Returns payload data.
     *
     * @return bool|int|float|string|array|null Payload data containing only `null`, scalars (`bool`, `int`, `float`,
     * `string`), or arrays composed of the same types recursively.
     *
     * @psalm-return MessageData
     */
    public function getData(): bool|int|float|string|array|null;

    /**
     * Returns message metadata: timings, attempt count, metrics, etc. Keys are always strings.
     *
     * @return array<string, bool|int|float|string|array|null> Metadata containing only `null`, scalars (`bool`, `int`,
     * `float`, `string`), or arrays composed of the same types recursively.
     *
     * @psalm-return MessageMetadata
     */
    public function getMetadata(): array;

    /**
     * Returns a new instance with the given message metadata.
     *
     * @param array<string, bool|int|float|string|array|null> $metadata Metadata containing only `null`, scalars (`bool`,
     * `int`, `float`, `string`), or arrays composed of the same types recursively.
     *
     * @psalm-param MessageMetadata $metadata
     */
    public function withMetadata(array $metadata): static;
}
