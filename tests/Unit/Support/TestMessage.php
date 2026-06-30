<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;

final class TestMessage extends Message
{
    public static function fromPayload(string $type, mixed $payload): static
    {
        return new self();
    }

    public function getType(): string
    {
        return 'test';
    }

    public function getPayload(): bool|int|float|string|array|null
    {
        return null;
    }
}
