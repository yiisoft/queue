<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace Yiisoft\Yii\Queue;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Serializer\SerializerInterface;
use Yiisoft\VarDumper\VarDumper;
use Yiisoft\Yii\Queue\Events\ExecEvent;
use Yiisoft\Yii\Queue\Events\PushEvent;

/**
 * Base Queue.
 *
 * @property null|int $workerPid
 *
 * @since 2.0.2
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
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
     * @var bool whether to enable strict job type control.
     *           Note that in order to enable type control, a pushing job must be [[JobInterface]] instance.
     *
     * @since 2.0.1
     */
    public $strictJobType = true;
    /**
     * @var int default time to reserve a job
     */
    public $ttr = 300;
    /**
     * @var int default attempt count
     */
    public $attempts = 1;

    private $pushTtr;
    private $pushDelay;
    private $pushPriority;

    /**
     * @var \Yiisoft\Serializer\SerializerInterface
     */
    private $serializer;
    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(
        SerializerInterface $serializer,
        EventDispatcherInterface $eventDispatcher,
        LoggerInterface $logger
    ) {
        $this->serializer = $serializer;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
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
    public function delay(int $value): self
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
    public function priority($value): self
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
    public function push($job)
    {
        $event = PushEvent::before($job, $this->pushTtr ?: (
        $job instanceof RetryableJobInterface
            ? $job->getTtr()
            : $this->ttr
        ), $this->pushDelay ?: 0, $this->pushPriority);

        $this->pushTtr = null;
        $this->pushDelay = null;
        $this->pushPriority = null;

        $this->trigger($event);
        if ($event->isPropagationStopped()) {
            return;
        }

        if ($this->strictJobType && !($event->job instanceof JobInterface)) {
            throw new \InvalidArgumentException('Job must be instance of JobInterface.');
        }

        $message = $this->serializer->serialize($event->job);
        $event->id = $this->pushMessage($message, $event->ttr, $event->delay, $event->priority);

        $this->trigger(PushEvent::after($event));

        return $event->id;
    }

    /**
     * @param string $message
     * @param int    $ttr      time to reserve in seconds
     * @param int    $delay
     * @param mixed  $priority
     *
     * @return string id of a job message
     */
    abstract protected function pushMessage($message, $ttr, $delay, $priority);

    /**
     * Uses for CLI drivers and gets process ID of a worker.
     *
     * @since 2.0.2
     */
    public function getWorkerPid()
    {
    }

    /**
     * @param string $id      of a job message
     * @param string $message
     * @param int    $ttr     time to reserve
     * @param int    $attempt number
     *
     * @return void
     */
    protected function handleMessage($id, $message, $ttr, $attempt): void
    {
        [$job, $error] = $this->unserializeMessage($message);

        $event = ExecEvent::before($id, $job, $ttr, $attempt, $error);
        $this->trigger($event);
        if ($event->isPropagationStopped()) {
            return;
        }
        if ($event->error) {
            $this->handleError($event);
            return;
        }

        try {
            $event->result = $event->job->execute($this);
        } catch (\Exception $error) {
            $event->error = $error;

            $this->handleError($event);
            return;
        } catch (\Throwable $error) {
            $event->error = $error;

            $this->handleError($event);
            return;
        }
        $this->trigger(ExecEvent::after($event));
    }

    /**
     * Unserializes.
     *
     * @param string $serialized message
     *
     * @return array pair of a job and error that
     */
    public function unserializeMessage($serialized)
    {
        try {
            $job = $this->serializer->unserialize($serialized);
        } catch (\Exception $e) {
            return [null, new InvalidJobException($serialized, $e->getMessage(), 0, $e)];
        }

        if ($job instanceof JobInterface) {
            return [$job, null];
        }

        return [null, new InvalidJobException($serialized, sprintf(
            'Job must be a JobInterface instance instead of %s.',
            VarDumper::dumpAsString($job)
        ))];
    }

    /**
     * @param ExecEvent $event
     *
     * @return bool
     *
     * @internal
     */
    public function handleError(ExecEvent $event)
    {
        $event->retry = $event->attempt < $this->attempts;
        if ($event->error instanceof InvalidJobException) {
            $event->retry = false;
        } elseif ($event->job instanceof RetryableJobInterface) {
            $event->retry = $event->job->canRetry($event->attempt, $event->error);
        }
        $this->trigger(ExecEvent::error($event));

        return !$event->retry;
    }

    /**
     * @param string $id of a job message
     *
     * @return bool
     */
    public function isWaiting($id): bool
    {
        return $this->status($id) === self::STATUS_WAITING;
    }

    /**
     * @param string $id of a job message
     *
     * @return bool
     */
    public function isReserved($id): bool
    {
        return $this->status($id) === self::STATUS_RESERVED;
    }

    /**
     * @param string $id of a job message
     *
     * @return bool
     */
    public function isDone($id): bool
    {
        return $this->status($id) === self::STATUS_DONE;
    }

    /**
     * @param string $id of a job message
     *
     * @return int status code
     */
    abstract public function status($id);
}
