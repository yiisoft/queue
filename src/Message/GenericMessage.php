<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

/**
 * A general-purpose immutable {@see MessageInterface} implementation that holds a message type and its payload data.
 *
 * Prefer creating custom message classes that better express your domain.
 */
final class GenericMessage extends Message
{
    /**
     * @param string $type A message type used to resolve the handler.
     * @param mixed $data Message payload data.
     */
    public function __construct(
        private readonly string $type,
        private readonly mixed $data,
    ) {}

    /**
     * Creates a new message instance from the given type and payload data.
     *
     * @param string $type A message type used to resolve the handler.
     * @param mixed $data Message payload data.
     *
     * @return MessageInterface The created message instance.
     */
    public static function fromData(string $type, mixed $data): MessageInterface
    {
        return new self($type, $data);
    }

    /**
     * Returns the message type used to resolve the handler.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns the message payload data.
     */
    public function getData(): mixed
    {
        return $this->data;
    }
}
