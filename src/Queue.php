<?php

namespace Yiisoft\Yii\Queue;

use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;
use Yiisoft\Yii\Queue\Payload\RetryablePayloadInterface;
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
        $payload = $event->getMessage()->getPayload();
        if (
            $payload instanceof RetryablePayloadInterface
            && !$event->getException() instanceof PayloadNotSupportedException
            && $event->getQueue() === $this
            && $payload->canRetry($event->getException())
        ) {
            $event->preventThrowing();
            $this->logger->debug('Retrying payload "{payload}".', ['payload' => $payload->getName()]);
            $payload->retry();
            $this->push($payload);
        }
    }

    /**
     * Pushes job into queue.
     *
     * @param PayloadInterface|mixed $payload
     *
     * @return string|null id of the pushed message
     */
    public function push(PayloadInterface $payload): ?string
    {
        $this->logger->debug('Preparing to push payload "{payload}".', ['payload' => $payload->getName()]);
        $event = new BeforePush($this, $payload);
        $this->eventDispatcher->dispatch($event);

        if ($this->driver->canPush($payload)) {
            $message = $this->driver->push($payload);
            $this->logger->debug('Successfully pushed payload "{payload}" to the queue.', ['payload' => $payload->getName()]);
        } else {
            $this->logger->error(
                'Payload "{payload}" is not supported by driver "{driver}."',
                [
                    'payload' => $payload->getName(),
                    'driver' => get_class($this->driver),
                ]
            );

            throw new PayloadNotSupportedException($this->driver, $payload);
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
            $this->handle($message);
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
        $this->driver->subscribe([$this, 'handle']);
        $this->logger->debug('Finish listening to the queue.');
    }

    /**
     * @param string $id A message id
     *
     * @return JobStatus
     *
     * @throws InvalidArgumentException when there is no such id in the driver
     */
    public function status(string $id): JobStatus
    {
        return $this->driver->status($id);
    }

    protected function handle(MessageInterface $message): void
    {
        $this->worker->process($message, $this);
    }
}
