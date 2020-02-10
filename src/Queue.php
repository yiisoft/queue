<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue;

use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Factory\Factory;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Events\AfterPushInterface;
use Yiisoft\Yii\Queue\Events\BeforePushInterface;
use Yiisoft\Yii\Queue\Events\JobFailureInterface;
use Yiisoft\Yii\Queue\Exceptions\InvalidJobException;
use Yiisoft\Yii\Queue\Exceptions\JobNotSupportedException;
use Yiisoft\Yii\Queue\Jobs\JobInterface;
use Yiisoft\Yii\Queue\Processors\WorkerInterface;

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
    protected Factory $factory;

    public function __construct(
        DriverInterface $driver,
        EventDispatcherInterface $dispatcher,
        Provider $provider,
        WorkerInterface $worker,
        Factory $factory
    ) {
        $this->driver = $driver;
        $this->eventDispatcher = $dispatcher;
        $this->worker = $worker;
        $this->provider = $provider;
        $this->factory = $factory;

        $provider->attach([$this, 'jobRetry']);
    }

    public function __destruct()
    {
        $this->provider->detach(JobFailureInterface::class);
    }

    public function jobRetry(JobFailureInterface $event): void
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
        /** @var BeforePushInterface $event */
        $event = $this->factory->create(BeforePushInterface::class, [$this, $job]);
        $this->eventDispatcher->dispatch($event);

        if ($this->driver->canPush($job)) {
            $message = $this->driver->push($job);
        } else {
            throw new JobNotSupportedException($this->driver, $job);
        }

        /** @var AfterPushInterface $event */
        $event = $this->factory->create(AfterPushInterface::class, [$this, $message]);
        $this->eventDispatcher->dispatch($event);

        return $message->getId();
    }

    /**
     * Execute all existing jobs and exit
     *
     * @param LoopInterface $loop
     */
    public function run(LoopInterface $loop): void
    {
        while ($loop->canContinue() && $message = $this->driver->nextMessage()) {
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
