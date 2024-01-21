<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Message;

interface MessageInterface
{
    /**
     * Returns handler name.
     *
     * @return string
     * @psalm-return class-string<MessageHandlerInterface>
     */
    public function getHandler(): string;

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
}
