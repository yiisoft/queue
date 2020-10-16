<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Driver;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Exception\BehaviorNotSupportedException;
use Yiisoft\Yii\Queue\Message\Behaviors\ExecutableBehaviorInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\QueueDependentInterface;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

final class SynchronousDriver implements DriverInterface, QueueDependentInterface
{
    private const BEHAVIORS_AVAILABLE = [];
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

        throw new InvalidArgumentException('There is no message with the given id.');
    }

    public function push(MessageInterface $message): void
    {
        $this->checkBehaviors($message);

        foreach ($message->getBehaviors() as $behavior) {
            if ($behavior instanceof ExecutableBehaviorInterface) {
                $behavior->execute();
            }
        }

        $key = count($this->messages) + $this->current;
        $this->messages[] = $message;

        $message->setId((string) $key);
    }

    public function subscribe(callable $handler): void
    {
        $this->run($handler);
    }

    private function checkBehaviors(MessageInterface $message): void
    {
        foreach ($message->getBehaviors() as $behavior) {
            $ok = false;
            foreach (self::BEHAVIORS_AVAILABLE as $available) {
                if ($behavior instanceof $available) {
                    $ok = true;
                    break;
                }
            }

            if ($ok === false) {
                throw new BehaviorNotSupportedException($this, $behavior);
            }
        }
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
