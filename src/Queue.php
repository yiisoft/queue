<?php
/**
 * @link http://www.yiiframework.com/
 *
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\queue;

use yii\base\Component;
use yii\exceptions\InvalidArgumentException;
use yii\helpers\VarDumper;
use yii\queue\serializers\SerializerInterface;

/**
 * Base Queue.
 *
 * @property null|int $workerPid
 *
 * @since 2.0.2
 *
 * @author Roman Zhuravlev <zhuravljov@gmail.com>
 */
abstract class Queue extends Component
{
    /**
     * @see Queue::isWaiting()
     */
    const STATUS_WAITING = 1;
    /**
     * @see Queue::isReserved()
     */
    const STATUS_RESERVED = 2;
    /**
     * @see Queue::isDone()
     */
    const STATUS_DONE = 3;

    /**
     * @var bool whether to enable strict job type control.
     *           Note that in order to enable type control, a pushing job must be [[JobInterface]] instance.
     *
     * @since 2.0.1
     */
    public $strictJobType = true;
    /**
     * @var SerializerInterface|array
     */
    private $serializer;
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
     * {@inheritdoc}
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * Sets TTR for job execute.
     *
     * @param int|mixed $value
     *
     * @return $this
     */
    public function ttr($value)
    {
        $this->pushTtr = $value;

        return $this;
    }

    /**
     * Sets delay for later execute.
     *
     * @param int|mixed $value
     *
     * @return $this
     */
    public function delay($value)
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
    public function priority($value)
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
            throw new InvalidArgumentException('Job must be instance of JobInterface.');
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
     * @return bool
     */
    protected function handleMessage($id, $message, $ttr, $attempt)
    {
        list($job, $error) = $this->unserializeMessage($message);
        $event = ExecEvent::before($id, $job, $ttr, $attempt, $error);
        $this->trigger($event);
        if ($event->isPropagationStopped()) {
            return true;
        }
        if ($event->error) {
            return $this->handleError($event);
        }

        try {
            $event->result = $event->job->execute($this);
        } catch (\Exception $error) {
            $event->error = $error;

            return $this->handleError($event);
        } catch (\Throwable $error) {
            $event->error = $error;

            return $this->handleError($event);
        }
        $this->trigger(ExecEvent::after($event));

        return true;
    }

    /**
     * Unserializes.
     *
     * @param string $id         of the job
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
        $this->trigger(ErrorEvent::after($event));

        return !$event->retry;
    }

    /**
     * @param string $id of a job message
     *
     * @return bool
     */
    public function isWaiting($id)
    {
        return $this->status($id) === self::STATUS_WAITING;
    }

    /**
     * @param string $id of a job message
     *
     * @return bool
     */
    public function isReserved($id)
    {
        return $this->status($id) === self::STATUS_RESERVED;
    }

    /**
     * @param string $id of a job message
     *
     * @return bool
     */
    public function isDone($id)
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
