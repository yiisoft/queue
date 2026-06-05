<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\MessageStatus;

use function count;

/**
 * In-memory implementation of {@see AdapterInterface} for testing and development purposes.
 *
 * This adapter stores messages in a local array and processes them sequentially within the same process without any
 * external queue backend. Messages are assigned incremental integer identifiers and are handled in FIFO order.
 *
 * Note: This implementation is not persistent and should not be used in production, as all data is lost when the
 * process ends.
 */
final class InMemoryAdapter implements AdapterInterface
{
    /**
     * @var MessageInterface[]
     * @psalm-var array<int, MessageInterface>
     */
    private array $messages = [];
    private int $current = 0;

    public function push(MessageInterface $message): MessageInterface
    {
        $id = count($this->messages) + $this->current;
        $this->messages[] = $message;

        return new IdEnvelope($message, $id);
    }

    public function runExisting(callable $handlerCallback): void
    {
        $result = true;
        while (isset($this->messages[$this->current]) && $result === true) {
            $result = $handlerCallback($this->messages[$this->current]);
            unset($this->messages[$this->current]);
            $this->current++;
        }
    }

    public function subscribe(callable $handlerCallback): void
    {
        $this->runExisting($handlerCallback);
    }

    public function status(int|string $id): MessageStatus
    {
        $id = (int) $id;

        if ($id < 0) {
            return MessageStatus::NOT_FOUND;
        }

        if ($id < $this->current) {
            return MessageStatus::DONE;
        }

        if (isset($this->messages[$id])) {
            return MessageStatus::WAITING;
        }

        return MessageStatus::NOT_FOUND;
    }

    public function hasStatusSupport(): bool
    {
        return true;
    }
}
