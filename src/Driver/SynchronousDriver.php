<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;
use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

final class SynchronousDriver implements DriverInterface
{
    private array $messages = [];
    private LoopInterface $loop;
    private WorkerInterface $worker;
    private int $current = 0;

    public function __construct(LoopInterface $loop, WorkerInterface $worker)
    {
        $this->loop = $loop;
        $this->worker = $worker;
    }

    public function __destruct()
    {
        $this->run([$this->worker, 'process']);
    }

    public function nextMessage(): ?MessageInterface
    {
        $message = null;

        if (isset($this->messages[$this->current])) {
            $message = $this->messages[$this->current];
            unset($this->messages[$this->current]);
            $this->current++;
        }

        return $message;
    }

    public function status(string $id): JobStatus
    {
        $id = (int) $id;

        if ($id < 0) {
            throw new InvalidArgumentException('This driver ids starts with 0');
        }

        if ($id < $this->current) {
            return JobStatus::done();
        }

        if (isset($this->messages[$id])) {
            return JobStatus::waiting();
        }

        throw new InvalidArgumentException('There is no message with the given id.');
    }

    public function push(MessageInterface $message): ?string
    {
        $key = count($this->messages) + $this->current;
        $this->messages[] = $message;

        return (string) $key;
    }

    public function subscribe(callable $handler): void
    {
        $this->run($handler);
    }

    public function canPush(MessageInterface $message): bool
    {
        $meta = $message->getPayloadMeta();

        return !isset($meta[PayloadInterface::META_KEY_DELAY]) && !isset($meta[PayloadInterface::META_KEY_PRIORITY]);
    }

    private function run(callable $handler): void
    {
        while ($this->loop->canContinue() && $message = $this->nextMessage()) {
            $handler($message);
        }
    }
}
