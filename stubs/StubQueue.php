<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;

/**
 * Stub queue that does nothing. Message status is always "done".
 */
final class StubQueue implements QueueInterface
{
    public function __construct(
        private string $name = 'default',
    ) {}

    public function push(MessageInterface $message): MessageInterface
    {
        return $message;
    }

    public function run(int $max = 0): int
    {
        return 0;
    }

    public function listen(): void {}

    public function status(int|string $id): MessageStatus
    {
        return MessageStatus::DONE;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
