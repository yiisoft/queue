<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

interface MessageInterface
{
    public static function fromData(string $type, mixed $data, array $metadata = []): self;

    /**
     * Returns message type.
     */
    public function getType(): string;

    /**
     * Returns payload data.
     */
    public function getData(): mixed;

    /**
     * Returns message metadata: timings, attempts count, metrics, etc.
     */
    public function getMetadata(): array;
}
