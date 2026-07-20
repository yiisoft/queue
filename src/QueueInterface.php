<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use Yiisoft\Queue\Message\MessageInterface;

interface QueueInterface
{
    /**
     * Pushes a message into the queue.
     *
     * @param MessageInterface $message The message to push.
     *
     * @return MessageInterface The pushed message, possibly enriched with metadata such as an assigned ID.
     */
    public function push(MessageInterface $message): MessageInterface;

    /**
     * @param int|string $id A message ID.
     *
     * @return MessageStatus
     */
    public function status(string|int $id): MessageStatus;

    /**
     * Returns the logical name of the queue.
     */
    public function getName(): string;
}
