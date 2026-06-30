<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * Represents a queue message with a type identifier, payload data, and metadata.
 *
 * @psalm-type MessagePayload = scalar|null|array<scalar|null|array>
 * @psalm-type MessageMeta = array<string, scalar|null|array<scalar|null|array>>
 */
interface MessageInterface
{
    /**
     * Creates a new message instance from the given type and payload data.
     *
     * @param string $type Message type.
     * @param bool|int|float|string|array|null $payload Message payload data. Must contain only `null`, scalars (`bool`,
     * `int`, `float`, `string`), or arrays composed of the same types recursively.
     *
     * @psalm-param MessagePayload $payload
     */
    public static function fromPayload(string $type, bool|int|float|string|array|null $payload): self;

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
     * @psalm-return MessagePayload
     */
    public function getPayload(): bool|int|float|string|array|null;

    /**
     * Returns message metadata: timings, attempt count, metrics, etc. Keys are always strings.
     *
     * @return array<string, bool|int|float|string|array|null> Metadata containing only `null`, scalars (`bool`, `int`,
     * `float`, `string`), or arrays composed of the same types recursively.
     *
     * @psalm-return MessageMeta
     */
    public function getMeta(): array;

    /**
     * Returns a new instance with the given message metadata.
     *
     * @param array<string, bool|int|float|string|array|null> $meta Metadata containing only `null`, scalars (`bool`,
     * `int`, `float`, `string`), or arrays composed of the same types recursively.
     *
     * @psalm-param MessageMeta $meta
     */
    public function withMeta(array $meta): static;
}
