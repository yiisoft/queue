<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\App;

use BackedEnum;
use Exception;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\StringNormalizer;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\MessageInterface;

final class FakeAdapter implements AdapterInterface
{
    public array $pushMessages = [];
    public string $channel = 'default';

    public function push(MessageInterface $message): MessageInterface
    {
        $this->pushMessages[] = $message;

        return $message;
    }

    public function runExisting(callable $handlerCallback): void
    {
        throw new Exception('`runExisting()` method is not implemented yet.');
    }

    public function status(string|int $id): MessageStatus
    {
        return MessageStatus::NOT_FOUND;
    }

    public function hasStatusSupport(): bool
    {
        return false;
    }

    public function subscribe(callable $handlerCallback): void
    {
        throw new Exception('`subscribe()` method is not implemented yet.');
    }

    public function withChannel(string|BackedEnum $channel): self
    {
        $new = clone $this;
        $new->channel = StringNormalizer::normalize($channel);

        return $new;
    }
}
