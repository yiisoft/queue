<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

interface MessageInterface
{
    /**
     * Returns payload data.
     *
     * @return mixed
     */
    public function getData(): mixed;

    /**
     * Returns message metadata: timings, attempts count, metrics, etc.
     *
     * @return array
     */
    public function getMetadata(): array;

    /**
     * Returns a new instance with the specified data.
     *
     * @return self
     */
    public function withData(mixed $data): self;

    /**
     * Returns a new instance with the specified metadata.
     *
     * @return self
     */
    public function withMetadata(array $metadata): self;
}
