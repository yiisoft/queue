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
use Throwable;
use Yiisoft\EventDispatcher\Dispatcher;
use Yiisoft\EventDispatcher\Provider\Provider;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Events\ExecEvent;
use Yiisoft\Yii\Queue\Events\PushEvent;
use Yiisoft\Yii\Queue\Jobs\JobInterface;
use Yiisoft\Yii\Queue\Jobs\RetryableJobInterface;
use Yiisoft\Yii\Queue\Processors\JobProcessorInterface;

/**
 * Base Queue.
 *
 * @property null|int $workerPid
 */
class Queue
{
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

    protected EventDispatcherInterface $eventDispatcher;
    protected LoggerInterface $logger;
    protected LogMessageFormatter $formatter;
    protected DriverInterface $driver;
    protected LoopInterface $loop;
    protected JobProcessorInterface $processor;
    protected Provider $provider;

    public function __construct(
        DriverInterface $driver,
        Dispatcher $dispatcher,
        Provider $provider,
        LoggerInterface $logger,
        LogMessageFormatter $formatter,
        LoopInterface $loop,
        JobProcessorInterface $processor
    ) {
        $this->driver = $driver;
        $this->eventDispatcher = $dispatcher;
        $this->logger = $logger;
        $this->formatter = $formatter;
        $this->loop = $loop;
        $this->processor = $processor;
        $this->provider = $provider;

        $provider->attach([$this, 'jobRetry']);
    }

    public function __destruct()
    {
        $this->provider->detach(ExecEvent::class);
    }

    public function jobRetry(ExecEvent $event): void
    {
        if (
            $event->name === ExecEvent::ERROR
            && !$event->error instanceof InvalidJobException
            && $event->job instanceof RetryableJobInterface
            && $event->getQueue() === $this
            && $event->job->canRetry($event->error)
        ) {
            $event->job->retry();
            $this->push($event->job);
        }
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

        $event->id = $this->driver->pushMessage($event->job, $event->ttr, $event->delay, $event->priority);

        $this->logger->info($this->formatter->getJobTitle($event) . ' is pushed');
        $this->eventDispatcher->dispatch(PushEvent::after($event));

        return $event->id;
    }

    public function run(): void
    {
        $pid = getmypid();
        $message = "Worker $pid is started.";
        $this->logger->info($message);

        while ($this->loop->canContinue() && $message = $this->driver->nextMessage()) {
            try {
                $this->processor->process($message, $this);
            } catch (Throwable $exception) {
            }
        }
    }

    public function listen(): void
    {
        $pid = getmypid();
        $message = "Worker $pid is started.";
        $this->logger->info($message);

        $handler = static function (MessageInterface $message) {
            try {
                $this->processor->process($message, $this);
            } catch (Throwable $exception) {
            }
        };

        $this->driver->subscribe($handler);
    }
}
