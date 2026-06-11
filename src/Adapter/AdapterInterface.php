<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Adapter;

use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\MessageInterface;

/**
 * Adapter interface for handling queue operations.
 */
interface AdapterInterface
{
    /**
     * Handle all existing messages in the queue.
     *
     * Processes all messages that are currently in the queue. The message passed to the handler callback
     * is guaranteed to not be an {@see Envelope} instance, only a plain {@see MessageInterface} with
     * merged metadata from any wrapping envelopes.
     *
     * @param callable $handlerCallback The handler which will handle messages (never an {@see Envelope} instance).
     * Returns `false` if it cannot continue handling messages.
     *
     * @psalm-param callable(MessageInterface): bool $handlerCallback
     */
    public function runExisting(callable $handlerCallback): void;

    /**
     * Returns status code of a message with the given ID.
     * Returns {@see MessageStatus::NOT_FOUND} when status tracking is not supported or there is no such id.
     *
     * @param int|string $id ID of a message.
     */
    public function status(string|int $id): MessageStatus;

    /**
     * Push a message to the queue.
     *
     * Adds a message to the queue. The message is guaranteed to not be an {@see Envelope} instance,
     * only a plain {@see MessageInterface} with merged metadata from any wrapping envelopes.
     *
     * @param MessageInterface $message The message to push. Never an {@see Envelope} instance.
     * @return MessageInterface The message with any modifications made by the adapter. May be wrapped in
     * an {@see Envelope} to carry additional metadata.
     */
    public function push(MessageInterface $message): MessageInterface;

    /**
     * Subscribe to the queue and process messages as they arrive.
     *
     * Listens to the queue and passes messages to the given handler as they become available.
     * The message passed to the handler callback is guaranteed to not be an {@see Envelope} instance,
     * only a plain {@see MessageInterface} with merged metadata from any wrapping envelopes.
     *
     * @param callable $handlerCallback The handler which will handle messages (never an {@see Envelope} instance).
     * Returns `false` if it cannot continue handling messages.
     *
     * @psalm-param callable(MessageInterface): bool $handlerCallback
     */
    public function subscribe(callable $handlerCallback): void;
}
