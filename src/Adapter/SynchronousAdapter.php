<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Adapter;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\QueueFactory;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

final class SynchronousAdapter implements AdapterInterface
{
    private array $messages = [];
    private int $current = 0;

    public function __construct(
        private WorkerInterface $worker,
        private QueueInterface $queue,
        private string $channel = QueueFactory::DEFAULT_CHANNEL_NAME,
    ) {
    }

    public function __destruct()
    {
        $this->runExisting(function (MessageInterface $message) {
            $this->worker->process($message, $this->queue);
        });
    }

    public function runExisting(callable $callback): void
    {
        while (isset($this->messages[$this->current])) {
            $callback($this->messages[$this->current]);
            unset($this->messages[$this->current]);
            $this->current++;
        }
    }

    public function status(string $id): JobStatus
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

    public function push(MessageInterface $message): void
    {
        $key = count($this->messages) + $this->current;
        $this->messages[] = $message;

        $message->setId((string) $key);
    }

    public function subscribe(callable $handler): void
    {
        $this->runExisting($handler);
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
