<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use InvalidArgumentException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Exception\BehaviorNotSupportedException;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\AdapterNotConfiguredException;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

class Queue
{
    protected EventDispatcherInterface $eventDispatcher;
    protected WorkerInterface $worker;
    protected LoopInterface $loop;
    private LoggerInterface $logger;
    protected ?AdapterInterface $adapter;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        WorkerInterface $worker,
        LoopInterface $loop,
        LoggerInterface $logger,
        ?AdapterInterface $adapter = null
    ) {
        $this->adapter = $adapter;
        $this->eventDispatcher = $dispatcher;
        $this->worker = $worker;
        $this->loop = $loop;
        $this->logger = $logger;

        if ($adapter instanceof QueueDependentInterface) {
            $adapter->setQueue($this);
        }
    }

    /**
     * Pushes a message into the queue.
     *
     * @param MessageInterface $message
     *
     * @throws BehaviorNotSupportedException
     */
    public function push(MessageInterface $message): void
    {
        $this->checkAdapter();

        $this->logger->debug('Preparing to push message "{message}".', ['message' => $message->getName()]);
        $this->eventDispatcher->dispatch(new BeforePush($this, $message));

        $this->adapter->push($message);

        $this->logger->debug(
            'Successfully pushed message "{name}" to the queue.',
            ['name' => $message->getName()]
        );

        $this->eventDispatcher->dispatch(new AfterPush($this, $message));
    }

    /**
     * Execute all existing jobs and exit
     *
     * @param int $max
     */
    public function run(int $max = 0): void
    {
        $this->checkAdapter();

        $this->logger->debug('Start processing queue messages.');
        $count = 0;

        while (
            ($max <= 0 || $max > $count)
            && $this->loop->canContinue()
            && $message = $this->adapter->nextMessage()
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
        $this->checkAdapter();

        $this->logger->debug('Start listening to the queue.');
        $this->adapter->subscribe(fn (MessageInterface $message) => $this->handle($message));
        $this->logger->debug('Finish listening to the queue.');
    }

    /**
     * @param string $id A message id
     *
     * @throws InvalidArgumentException when there is no such id in the adapter
     *
     * @return JobStatus
     */
    public function status(string $id): JobStatus
    {
        return $this->adapter->status($id);
    }

    protected function handle(MessageInterface $message): void
    {
        $this->worker->process($message, $this);
    }

    public function withAdapter(AdapterInterface $adapter): self
    {
        $instance = clone $this;
        $instance->adapter = $adapter;

        if ($adapter instanceof QueueDependentInterface) {
            $adapter->setQueue($instance);
        }

        return $instance;
    }

    private function checkAdapter(): void
    {
        if ($this->adapter === null) {
            throw new AdapterNotConfiguredException();
        }
    }
}
