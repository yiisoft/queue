<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use Yiisoft\Queue\Message\MessageInterface;

/** Produces messages and obtains their delivery status. */
interface QueueProducerInterface
{
    /** Pushes a message and returns its possibly enriched representation. */
    public function push(MessageInterface $message): MessageInterface;

    /** Returns the status of a message ID. */
    public function status(string|int $id): MessageStatus;

    /** Returns the logical queue name. */
    public function getName(): string;
}
