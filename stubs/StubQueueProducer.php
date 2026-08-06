<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\QueueProducerInterface;

final class StubQueueProducer implements QueueProducerInterface
{
    public function __construct(private string $name = 'default') {}

    public function push(MessageInterface $message): MessageInterface
    {
        return $message;
    }

    public function status(string|int $id): MessageStatus
    {
        return MessageStatus::DONE;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
