<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Adapter;

use BackedEnum;
use InvalidArgumentException;
use Yiisoft\Queue\ChannelNormalizer;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\Worker\WorkerInterface;
use Yiisoft\Queue\Message\IdEnvelope;

final class SynchronousAdapter implements AdapterInterface
{
    private array $messages = [];
    private int $current = 0;
    private string $channel;

    public function __construct(
        private readonly WorkerInterface $worker,
        private readonly QueueInterface $queue,
        string|BackedEnum $channel = QueueInterface::DEFAULT_CHANNEL,
    ) {
        $this->channel = ChannelNormalizer::normalize($channel);
    }

    public function __destruct()
    {
        $this->runExisting(function (MessageInterface $message): bool {
            $this->worker->process($message, $this->queue);

            return true;
        });
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

    public function status(string|int $id): JobStatus
    {
        $id = (int) $id;

        if ($id < 0) {
            throw new InvalidArgumentException('This adapter IDs start with 0.');
        }

        if ($id < $this->current) {
            return JobStatus::DONE;
        }

        if (isset($this->messages[$id])) {
            return JobStatus::WAITING;
        }

        throw new InvalidArgumentException('There is no message with the given ID.');
    }

    public function push(MessageInterface $message): MessageInterface
    {
        $key = count($this->messages) + $this->current;
        $this->messages[] = $message;

        return new IdEnvelope($message, $key);
    }

    public function subscribe(callable $handlerCallback): void
    {
        $this->runExisting($handlerCallback);
    }

    public function withChannel(string|BackedEnum $channel): self
    {
        $channel = ChannelNormalizer::normalize($channel);

        if ($channel === $this->channel) {
            return $this;
        }

        $new = clone $this;
        $new->channel = $channel;
        $new->messages = [];

        return $new;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }
}
