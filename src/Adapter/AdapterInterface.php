<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Adapter;

use InvalidArgumentException;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;

interface AdapterInterface
{
    /**
     * Returns the first message from the queue if it exists (null otherwise).
     *
     * @param callable(MessageInterface): bool  $handlerCallback The handler which will handle messages. Returns false if it cannot continue handling messages
     */
    public function runExisting(callable $handlerCallback): void;

    /**
     * Returns status code of a message with the given id.
     *
     * @param string $id ID of a job message.
     *
     * @throws InvalidArgumentException When there is no such id in the adapter.
     *
     * @return JobStatus
     */
    public function status(string|int $id): JobStatus;

    /**
     * Pushing a message to the queue. Adapter sets message ID if available.
     */
    public function push(MessageInterface $message): MessageInterface;

    /**
     * Listen to the queue and pass messages to the given handler as they come.
     *
     * @param callable(MessageInterface): bool $handlerCallback The handler which will handle messages. Returns false if it cannot continue handling messages.
     */
    public function subscribe(callable $handlerCallback): void;

    public function withChannel(string $channel): self;

    public function getChannelName(): string;
}
