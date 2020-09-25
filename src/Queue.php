<?php

namespace Yiisoft\Yii\Queue;

use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Event\JobFailure;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Payload\PayloadFactoryInterface;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

class Queue
{
    protected EventDispatcherInterface $eventDispatcher;
    protected DriverInterface $driver;
    protected WorkerInterface $worker;
    protected LoopInterface $loop;
    private LoggerInterface $logger;
    /**
     * @var PayloadFactoryInterface
     */
    private PayloadFactoryInterface $payloadFactory;

    public function __construct(
        DriverInterface $driver,
        EventDispatcherInterface $dispatcher,
        WorkerInterface $worker,
        LoopInterface $loop,
        LoggerInterface $logger,
        PayloadFactoryInterface $payloadFactory
    ) {
        $this->driver = $driver;
        $this->eventDispatcher = $dispatcher;
        $this->worker = $worker;
        $this->loop = $loop;
        $this->logger = $logger;
        $this->payloadFactory = $payloadFactory;

        if ($driver instanceof QueueDependentInterface) {
            $driver->setQueue($this);
        }
    }

    public function jobRetry(JobFailure $event): void
    {
        if (
            $event->getQueue() === $this
            && !$event->getException() instanceof PayloadNotSupportedException
            && ($event->getMessage()->getPayloadMeta()[PayloadInterface::META_KEY_ATTEMPTS] ?? 0) > 0
        ) {
            $event->preventThrowing();
            $attemptsLeft = $event->getMessage()->getPayloadMeta()[PayloadInterface::META_KEY_ATTEMPTS] - 1;
            $payload = $this->payloadFactory->createPayload(
                $event->getMessage(),
                [PayloadInterface::META_KEY_ATTEMPTS => $attemptsLeft]
            );
            $this->logger->debug(
                'Retrying payload "{payload}".',
                ['payload' => $event->getMessage()->getPayloadName()]
            );

            $this->push($payload);
        }
    }

    /**
     * Pushes job into queue.
     *
     * @param PayloadInterface $payload
     *
     * @return string|null id of the pushed message
     */
    public function push(PayloadInterface $payload): ?string
    {
        $this->logger->debug('Preparing to push payload "{payload}".', ['payload' => $payload->getName()]);
        $message = $this->payloadFactory->createMessage($payload);
        $this->eventDispatcher->dispatch(new BeforePush($this, $message));

        if ($this->driver->canPush($message)) {
            $message->setId($this->driver->push($message));
            $this->logger->debug(
                'Successfully pushed message "{name}" to the queue.',
                ['name' => $message->getPayloadName()]
            );
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

        $this->eventDispatcher->dispatch(new AfterPush($this, $message));

        return $message->getId();
    }

    /**
     * Execute all existing jobs and exit
     *
     * @param int $max
     */
    public function run(int $max = 0): void
    {
        $this->logger->debug('Start processing queue messages.');
        $count = 0;

        while (
            ($max <= 0 || $max > $count)
            && $this->loop->canContinue()
            && $message = $this->driver->nextMessage()
        ) {
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
        $this->driver->subscribe(fn (MessageInterface $message) => $this->handle($message));
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
