<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Adapter;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\Behaviors\ExecutableBehaviorInterface;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\QueueDependentInterface;
use Yiisoft\Yii\Queue\QueueInterface;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

final class SynchronousAdapter implements AdapterInterface, QueueDependentInterface
{
    private const BEHAVIORS_AVAILABLE = [];
    private array $messages = [];
    /** @psalm-suppress PropertyNotSetInConstructor */
    private QueueInterface $queue;
    private LoopInterface $loop;
    private WorkerInterface $worker;
    private int $current = 0;
    private ?BehaviorChecker $behaviorChecker;
    private string $channel;

    public function __construct(
        LoopInterface $loop,
        WorkerInterface $worker,
        string $channel = 'default',
        ?BehaviorChecker $behaviorChecker = null
    ) {
        $this->loop = $loop;
        $this->worker = $worker;
        $this->channel = $channel;
        $this->behaviorChecker = $behaviorChecker;
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
        $behaviors = $message->getBehaviors();
        if ($this->behaviorChecker !== null) {
            $this->behaviorChecker->check(self::class, $behaviors, self::BEHAVIORS_AVAILABLE);
        }

        foreach ($behaviors as $behavior) {
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

    public function setQueue(QueueInterface $queue): void
    {
        $this->queue = $queue;
    }

    public function withChannel(string $channel): self
    {
        if ($channel === $this->channel) {
            return $this;
        }

        $instance = clone $this;
        $instance->channel = $channel;
        $instance->messages = [];

        return $instance;
    }

    private function run(callable $handler): void
    {
        while ($this->loop->canContinue() && $message = $this->nextMessage()) {
            $handler($message, $this->queue);
        }
    }
}
