<?php

namespace Yiisoft\Yii\Queue;

use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Events\AfterPush;
use Yiisoft\Yii\Queue\Events\BeforePush;
use Yiisoft\Yii\Queue\Events\JobFailure;
use Yiisoft\Yii\Queue\Exceptions\InvalidJobException;
use Yiisoft\Yii\Queue\Exceptions\JobNotSupportedException;
use Yiisoft\Yii\Queue\Jobs\JobInterface;
use Yiisoft\Yii\Queue\Workers\WorkerInterface;

/**
 * Base Queue.
 *
 * @property null|int $workerPid
 */
class Queue
{
    protected EventDispatcherInterface $eventDispatcher;
    protected DriverInterface $driver;
    protected WorkerInterface $worker;
    protected Provider $provider;
    protected LoopInterface $loop;

    public function __construct(
        DriverInterface $driver,
        EventDispatcherInterface $dispatcher,
        Provider $provider,
        WorkerInterface $worker,
        LoopInterface $loop
    ) {
        $this->driver = $driver;
        $this->eventDispatcher = $dispatcher;
        $this->worker = $worker;
        $this->provider = $provider;
        $this->loop = $loop;

        $provider->attach([$this, 'jobRetry']);
    }

    public function __destruct()
    {
        $this->provider->detach(JobFailure::class);
    }

    public function jobRetry(JobFailure $event): void
    {
        if (
            !$event->getException() instanceof InvalidJobException
            && !$event->getException() instanceof JobNotSupportedException
            && $event->getQueue() === $this
            && $event->getMessage()->getJob()->canRetry($event->getException())
        ) {
            $event->getMessage()->getJob()->retry();
            $this->push($event->getMessage()->getJob());
        }
    }

    /**
     * Pushes job into queue.
     *
     * @param JobInterface|mixed $job
     *
     * @return string|null id of a job message
     */
    public function push(JobInterface $job): ?string
    {
        $event = new BeforePush($this, $job);
        $this->eventDispatcher->dispatch($event);

        if ($this->driver->canPush($job)) {
            $message = $this->driver->push($job);
        } else {
            throw new JobNotSupportedException($this->driver, $job);
        }

        $event = new AfterPush($this, $message);
        $this->eventDispatcher->dispatch($event);

        return $message->getId();
    }

    /**
     * Execute all existing jobs and exit
     */
    public function run(): void
    {
        while ($this->loop->canContinue() && $message = $this->driver->nextMessage()) {
            $this->worker->process($message, $this);
        }
    }

    /**
     * Listen to the queue and execute jobs as they come
     */
    public function listen(): void
    {
        $handler = function (MessageInterface $message) {
            $this->worker->process($message, $this);
        };

        $this->driver->subscribe($handler);
    }
}
