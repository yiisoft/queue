<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\MessageInterface;

/**
 * Stub adapter that does nothing. Message status is always "done".
 */
final class StubAdapter implements AdapterInterface
{
    public function runExisting(callable $handlerCallback): void
    {
    }

    public function status(int|string $id): MessageStatus
    {
        return MessageStatus::DONE;
    }

    public function push(MessageInterface $message): MessageInterface
    {
        return $message;
    }

    public function subscribe(callable $handlerCallback): void
    {
    }
}
