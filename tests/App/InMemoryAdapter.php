<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\App;

use InvalidArgumentException;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\MessageStatus;

use function count;

/**
 * Simple in-memory adapter for tests.
 * Stores pushed messages and serves them on runExisting()/subscribe().
 */
final class InMemoryAdapter implements AdapterInterface
{
    private array $messages = [];
    private int $current = 0;

    public function push(MessageInterface $message): MessageInterface
    {
        $key = count($this->messages) + $this->current;
        $this->messages[] = $message;

        return new IdEnvelope($message, $key);
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

        if ($id < $this->current) {
            return MessageStatus::DONE;
        }

        if (isset($this->messages[$id])) {
            return MessageStatus::WAITING;
        }

        throw new InvalidArgumentException('There is no message with the given ID.');
    }
}
