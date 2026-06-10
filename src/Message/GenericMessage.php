<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * A general-purpose immutable {@see MessageInterface} implementation that holds a message type and its payload data.
 *
 * Prefer creating custom message classes that better express your domain.
 *
 * @psalm-import-type MessageData from MessageInterface
 */
final class GenericMessage extends Message
{
    /**
     * @param string $type A message type used to resolve the handler.
     * @param bool|int|float|string|array|null $data Message payload data. Must contain only `null`, scalars (`bool`,
     * `int`, `float`, `string`), or arrays composed of the same types recursively.
     *
     * @psalm-param MessageData $data
     */
    public function __construct(
        private readonly string $type,
        private readonly bool|int|float|string|array|null $data,
    ) {}

    public static function fromData(string $type, bool|int|float|string|array|null $data): MessageInterface
    {
        return new self($type, $data);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getData(): bool|int|float|string|array|null
    {
        return $this->data;
    }
}
