<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue;

use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Yiisoft\Serializer\SerializerInterface;
use Yiisoft\VarDumper\VarDumper;
use Yiisoft\Yii\Queue\Events\ExecEvent;
use Yiisoft\Yii\Queue\Events\PushEvent;

/**
 * Base Queue.
 *
 * @property null|int $workerPid
 */
abstract class Queue
{
    /**
     * @see Queue::isWaiting()
     */
    public const STATUS_WAITING = 1;
    /**
     * @see Queue::isReserved()
     */
    public const STATUS_RESERVED = 2;
    /**
     * @see Queue::isDone()
     */
    public const STATUS_DONE = 3;

    /**
     * @var int default time to reserve a job
     */
    public int $ttrDefault = 300;
    /**
     * @var int default attempt count
     */
    public int $attempts = 1;

    private ?int $pushTtr = null;
    private ?int $pushDelay = null;
    private ?int $pushPriority = null;

    private SerializerInterface $serializer;
    private EventDispatcherInterface $eventDispatcher;
    private LoggerInterface $logger;
    private LogMessageFormatter $formatter;
    private QueueDriverInterface $driver;

    public function __construct(
        QueueDriverInterface $driver,
        SerializerInterface $serializer,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger,
        LogMessageFormatter $formatter
    ) {
        $this->driver = $driver;
        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
        $this->formatter = $formatter;
    }

    /**
     * Sets TTR for job execute.
     *
     * @param int|mixed $value
     *
     * @return $this
     */
    public function ttr(int $value): self
    {
        $this->pushTtr = $value;

        return $this;
    }

    /**
     * Sets delay for later execute.
     *
     * @param int $value
     *
     * @return $this
     */
    public function withDelay(int $value): self
    {
        $this->pushDelay = $value;

        return $this;
    }

    /**
     * Sets job priority.
     *
     * @param mixed $value
     *
     * @return $this
     */
    public function withPriority($value): self
    {
        $this->pushPriority = $value;

        return $this;
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
        if ($this->pushTtr === null) {
            if ($job instanceof RetryableJobInterface) {
                $ttr = $job->getTtr();
            } else {
                $ttr = $this->ttrDefault;
            }
        } else {
            $ttr = $this->pushTtr;
        }

        $event = PushEvent::before($job, $ttr, $this->pushDelay ?? 0, $this->pushPriority);

        $this->pushTtr = null;
        $this->pushDelay = null;
        $this->pushPriority = null;

        $this->eventDispatcher->dispatch($event);

        $message = $this->serializer->serialize($event->job);
        $event->id = $this->driver->pushMessage($message, $event->ttr, $event->delay, $event->priority);

        $this->logger->info($this->formatter->getJobTitle($event) . ' is pushed');
        $this->eventDispatcher->dispatch(PushEvent::after($event));

        return $event->id;
    }

    /**
     * Uses for CLI drivers and gets process ID of a worker.
     */
    public function getWorkerPid(): ?string
    {
        return null;
    }

    /**
     * @param string $id of a job message
     * @param string $message
     * @param int $ttr time to reserve
     * @param int $attempt number
     *
     * @return void
     */
    protected function handleMessage($id, $message, $ttr, $attempt): void
    {
        $job = $error = null;

        try {
            $job = $this->unserializeMessage($message);
        } catch (InvalidJobException $error) {
        }

        $event = ExecEvent::before($id, $job, $ttr, $attempt, $error);
        $this->eventDispatcher->dispatch($event);

        $title = $this->formatter->getExecTitle($event);
        $this->logger->info("$title is started.");

        if ($event->error !== null) {
            $this->handleError($event);
            return;
        }

        try {
            $event->result = $event->job->execute($this);
            $this->logger->info("$title is finished.");
        } catch (Throwable $error) {
            $event->error = $error;
            $this->handleError($event);
            return;
        }

        $this->eventDispatcher->dispatch(ExecEvent::after($event));
    }

    /**
     * Unserializes a message.
     *
     * @param string $serialized message
     *
     * @return JobInterface a job
     *
     * @throws InvalidJobException
     */
    public function unserializeMessage($serialized): JobInterface
    {
        try {
            $job = $this->serializer->unserialize($serialized);
        } catch (Exception $e) {
            throw new InvalidJobException($serialized, $e->getMessage(), 0, $e);
        }

        if ($job instanceof JobInterface) {
            return $job;
        }

        throw new InvalidJobException($serialized, sprintf(
            'Job must be a JobInterface instance instead of %s.',
            VarDumper::dumpAsString($job)
        ));
    }

    /**
     * @param ExecEvent $event
     *
     * @return bool
     *
     * @internal
     */
    public function handleError(ExecEvent $event): bool
    {
        $title = $this->formatter->getExecTitle($event);
        $this->logger->error("$title is finished with error: $event->error.");

        $event->retry = $event->attempt < $this->attempts;
        if ($event->error instanceof InvalidJobException) {
            $event->retry = false;
        } elseif ($event->job instanceof RetryableJobInterface) {
            $event->retry = $event->job->canRetry($event->attempt, $event->error);
        }

        $this->eventDispatcher->dispatch(ExecEvent::error($event));

        return !$event->retry;
    }

    /**
     * @param string $id of a job message
     *
     * @return bool
     */
    public function isWaiting($id): bool
    {
        return $this->driver->status($id) === self::STATUS_WAITING;
    }

    /**
     * @param string $id of a job message
     *
     * @return bool
     */
    public function isReserved($id): bool
    {
        return $this->driver->status($id) === self::STATUS_RESERVED;
    }

    /**
     * @param string $id of a job message
     *
     * @return bool
     */
    public function isDone($id): bool
    {
        return $this->driver->status($id) === self::STATUS_DONE;
    }
}
