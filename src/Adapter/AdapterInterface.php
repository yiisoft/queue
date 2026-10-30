<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Adapter;

use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\MessageInterface;

interface AdapterInterface
{
    /**
     * Passes existing messages to the given handler, in the order the adapter delivers them, until the queue is
     * empty or the handler refuses a message.
     *
     * The handler returns `false` for a message it did not consume: that message must stay in the queue, and no
     * further messages are passed. Only a message the handler returned `true` for may be removed from the queue.
     *
     * @param callable(MessageInterface): bool $handlerCallback The handler which will handle messages. Returns false for a message it did not consume.
     */
    public function runExisting(callable $handlerCallback): void;

    /**
     * Returns status code of a message with the given ID.
     * Returns {@see MessageStatus::NOT_FOUND} when status tracking is not supported or there is no such id.
     * Use {@see hasStatusSupport()} to tell unsupported tracking apart from a missing ID.
     *
     * @param int|string $id ID of a message.
     */
    public function status(string|int $id): MessageStatus;

    /**
     * Whether the adapter supports message status tracking.
     *
     * When `false`, {@see status()} always returns {@see MessageStatus::NOT_FOUND}.
     */
    public function hasStatusSupport(): bool;

    /**
     * Pushing a message to the queue. Adapter sets message ID if available.
     */
    public function push(MessageInterface $message): MessageInterface;

    /**
     * Listen to the queue and pass messages to the given handler as they come.
     *
     * The handler returns `false` for a message it did not consume, as in {@see runExisting()}: that message must
     * stay in the queue.
     *
     * @param callable(MessageInterface): bool $handlerCallback The handler which will handle messages. Returns false for a message it did not consume.
     */
    public function subscribe(callable $handlerCallback): void;
}
