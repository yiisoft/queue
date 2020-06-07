<?php

namespace Yiisoft\Yii\Queue;

use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Exception\InvalidJobException;
use Yiisoft\Yii\Queue\Exception\JobNotSupportedException;
use Yiisoft\Yii\Queue\Job\JobInterface;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

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
    private LoggerInterface $logger;

    public function __construct(
        DriverInterface $driver,
        EventDispatcherInterface $dispatcher,
        Provider $provider,
        WorkerInterface $worker,
        LoopInterface $loop,
        LoggerInterface $logger
    ) {
        $this->driver = $driver;
        $this->eventDispatcher = $dispatcher;
        $this->worker = $worker;
        $this->provider = $provider;
        $this->loop = $loop;
        $this->logger = $logger;

        if ($driver instanceof QueueDependentInterface) {
            $driver->setQueue($this);
        }
    }

    public function jobRetry(JobFailure $event): void
    {
        if (
            !$event->getException() instanceof JobNotSupportedException
            && $event->getQueue() === $this
            && $event->getMessage()->getJob()->canRetry($event->getException())
        ) {
            $this->logger->debug('Retrying job "{job}".', ['job' => get_class($event->getMessage()->getJob())]);
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
        $this->logger->debug('Preparing to push job "{job}".', ['job' => get_class($job)]);
        $event = new BeforePush($this, $job);
        $this->eventDispatcher->dispatch($event);

        if ($this->driver->canPush($job)) {
            $message = $this->driver->push($job);
            $this->logger->debug('Successfully pushed job "{job}" to the queue.', ['job' => get_class($job)]);
        } else {
            $this->logger->error(
                'Job "{job}" is not supported by driver "{driver}."',
                [
                    'job' => get_class($job),
                    'driver' => get_class($this->driver),
                ]
            );

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
        $this->logger->debug('Start processing queue messages.');
        $count = 0;

        while ($this->loop->canContinue() && $message = $this->driver->nextMessage()) {
            $this->worker->process($message, $this);
            $count++;
        }

        $this->logger->debug(
            'Finish processing queue messages. There were {count} messages to work with.',
            ['count' => $count]
        );
    }

    /**
     * Listen to the queue and execute jobs as they come
     */
    public function listen(): void
    {
        $this->logger->debug('Start listening to the queue.');
        $handler = function (MessageInterface $message) {
            $this->worker->process($message, $this);
        };

        $this->driver->subscribe($handler);
        $this->logger->debug('Finish listening to the queue.');
    }

    /**
     * @param string $id A job id
     *
     * @return JobStatus
     *
     * @throws InvalidArgumentException when there is no such id in the driver
     */
    public function status(string $id): JobStatus
    {
        return $this->driver->status($id);
    }
}
