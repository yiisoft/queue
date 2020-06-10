<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Job\DelayableJobInterface;
use Yiisoft\Yii\Queue\Job\JobInterface;
use Yiisoft\Yii\Queue\Job\PrioritisedJobInterface;
use Yiisoft\Yii\Queue\Message;
use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\QueueDependentInterface;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

final class SynchronousDriver implements DriverInterface, QueueDependentInterface
{
    private array $messages = [];
    private Queue $queue;
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

        throw new InvalidArgumentException('There is no job with the given id.');
    }

    public function push(JobInterface $job): MessageInterface
    {
        $key = count($this->messages) + $this->current;
        $message = new Message((string) $key, $job);
        $this->messages[] = $message;

        return $message;
    }

    public function subscribe(callable $handler): void
    {
        $this->run($handler);
    }

    public function canPush(JobInterface $job): bool
    {
        return !($job instanceof DelayableJobInterface || $job instanceof PrioritisedJobInterface);
    }

    public function setQueue(Queue $queue): void
    {
        $this->queue = $queue;
    }

    private function run(callable $handler): void
    {
        while ($this->loop->canContinue() && $message = $this->nextMessage()) {
            $handler($message, $this->queue);
        }
    }
}
