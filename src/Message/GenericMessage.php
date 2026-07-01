<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * A general-purpose immutable {@see MessageInterface} implementation that holds a message type and its payload data.
 *
 * Prefer creating custom message classes that better express your domain.
 *
 * @psalm-import-type MessagePayload from MessageInterface
 */
final class GenericMessage extends Message
{
    /**
     * @param string $type A message type used to resolve the handler.
     * @param bool|int|float|string|array|null $payload Message payload data. Must contain only `null`, scalars (`bool`,
     * `int`, `float`, `string`), or arrays composed of the same types recursively.
     *
     * @psalm-param MessagePayload $payload
     */
    public function __construct(
        private readonly string $type,
        private readonly bool|int|float|string|array|null $payload,
    ) {}

    public static function fromPayload(string $type, bool|int|float|string|array|null $payload): static
    {
        return new self($type, $payload);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getPayload(): bool|int|float|string|array|null
    {
        return $this->payload;
    }
}
