<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Support;

use Yiisoft\Queue\Message\MessageInterface;

final class TestMessage implements MessageInterface
{
    public static function fromData(string $handlerName, mixed $data, array $metadata = []): MessageInterface
    {
        return new self();
    }

    public function getHandlerName(): string
    {
        return 'test';
    }

    public function getData(): mixed
    {
        return null;
    }

    public function getMetadata(): array
    {
        return [];
    }
}
