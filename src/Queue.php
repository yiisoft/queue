<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\AdapterNotConfiguredException;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

final class Queue implements QueueInterface
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
    }

    public function push(MessageInterface $message): void
    {
        $this->checkAdapter();

        $this->logger->debug('Preparing to push message "{message}".', ['message' => $message->getName()]);
        $this->eventDispatcher->dispatch(new BeforePush($this, $message));

        /** @psalm-suppress PossiblyNullReference */
        $this->adapter->push($message);

        $this->logger->debug(
            'Successfully pushed message "{name}" to the queue.',
            ['name' => $message->getName()]
        );

        $this->eventDispatcher->dispatch(new AfterPush($this, $message));
    }

    public function run(int $max = 0): void
    {
        $this->checkAdapter();

        $this->logger->debug('Start processing queue messages.');
        $count = 0;

        $callback = function (MessageInterface $message) use (&$max, &$count): bool {
            if (($max > 0 && $max <= $count) || !$this->loop->canContinue()) {
                return false;
            }

            $this->handle($message);
            $count++;

            return true;
        };

        /** @psalm-suppress PossiblyNullReference */
        $this->adapter->runExisting($callback);

        $this->logger->debug(
            'Finish processing queue messages. There were {count} messages to work with.',
            ['count' => $count]
        );
    }

    public function listen(): void
    {
        $this->checkAdapter();

        $this->logger->debug('Start listening to the queue.');
        /** @psalm-suppress PossiblyNullReference */
        $this->adapter->subscribe(fn (MessageInterface $message) => $this->handle($message));
        $this->logger->debug('Finish listening to the queue.');
    }

    public function status(string $id): JobStatus
    {
        $this->checkAdapter();

        /** @psalm-suppress PossiblyNullReference */
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
