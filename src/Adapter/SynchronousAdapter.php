<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Adapter;

use InvalidArgumentException;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Message\MessageSerializerInterface;
use Yiisoft\Queue\QueueFactoryInterface;
use Yiisoft\Queue\Message\IdEnvelope;

final class SynchronousAdapter implements AdapterInterface
{
    private array $messages = [];
    private int $current = 0;

    public function __construct(
        private MessageSerializerInterface $messageSerializer,
        private string $channel = QueueFactoryInterface::DEFAULT_CHANNEL_NAME,
    ) {
    }

    public function runExisting(callable $handlerCallback): void
    {
        $result = true;
        while ($result === true && isset($this->messages[$this->current])) {
            $result = $handlerCallback(
                $this->messageSerializer->unserialize($this->messages[$this->current])
            );
            unset($this->messages[$this->current]);
            $this->current++;
        }
    }

    public function status(string|int $id): JobStatus
    {
        $id = (int) $id;

        if ($id < 0) {
            throw new InvalidArgumentException('This adapter IDs start with 0.');
        }

        if ($id < $this->current) {
            return JobStatus::done();
        }

        if (isset($this->messages[$id])) {
            return JobStatus::waiting();
        }

        throw new InvalidArgumentException('There is no message with the given ID.');
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $key = count($this->messages) + $this->current;
        $newMessage = new IdEnvelope($message, $key);
        $this->messages[] = $this->messageSerializer->serialize($newMessage);

        return $newMessage;
    }

    public function subscribe(callable $handlerCallback): void
    {
        $this->runExisting($handlerCallback);
    }

    public function withChannel(string $channel): self
    {
        if ($channel === $this->channel) {
            return $this;
        }

        $new = clone $this;
        $new->channel = $channel;
        $new->messages = [];

        return $new;
    }
}
