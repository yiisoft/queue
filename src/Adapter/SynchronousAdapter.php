<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Adapter;

use InvalidArgumentException;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\IdEnvelope;

final class SynchronousAdapter implements AdapterInterface, ImmediateProcessingAdapterInterface
{
    private int $processed = 0;

    public function runExisting(callable $handlerCallback): void
    {
        // Messages are handled immediately in Queue::push().
    }

    public function status(string|int $id): MessageStatus
    {
        $id = (int) $id;

        if ($id < 0) {
            throw new InvalidArgumentException('This adapter IDs start with 0.');
        }

        if ($id < $this->processed) {
            return MessageStatus::DONE;
        }

        throw new InvalidArgumentException('There is no message with the given ID.');
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $key = $this->processed;
        $this->processed++;

        return new IdEnvelope($message, $key);
    }

    public function subscribe(callable $handlerCallback): void
    {
        $this->runExisting($handlerCallback);
    }
}
