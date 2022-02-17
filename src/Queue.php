<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Psr\Log\LoggerInterface;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\AdapterNotConfiguredException;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Worker\WorkerInterface;

final class Queue implements QueueInterface
{
    protected WorkerInterface $worker;
    protected LoopInterface $loop;
    protected ?AdapterInterface $adapter;
    private LoggerInterface $logger;
    private string $channelName;

    public function __construct(
        WorkerInterface $worker,
        LoopInterface $loop,
        LoggerInterface $logger,
        ?AdapterInterface $adapter = null,
        string $channelName = QueueFactoryInterface::DEFAULT_CHANNEL_NAME
    ) {
        $this->adapter = $adapter;
        $this->worker = $worker;
        $this->loop = $loop;
        $this->logger = $logger;
        $this->channelName = $channelName;
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }

    public function push(MessageInterface $message): void
    {
        $this->checkAdapter();

        $this->logger->info(
            'Preparing to push message with handler name "{handlerName}".',
            ['handlerName' => $message->getHandlerName()]
        );

        /** @psalm-suppress PossiblyNullReference */
        $this->adapter->push($message);

        $this->logger->info(
            'Successfully pushed message with handler name "{handlerName}" to the queue. Assigned ID #{id}.',
            ['name' => $message->getHandlerName(), 'id' => $message->getId() ?? 'null']
        );
    }

    public function run(int $max = 0): void
    {
        $this->checkAdapter();

        $this->logger->info('Start processing queue messages.');
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

        $this->logger->info(
            'Finish processing queue messages. There were {count} messages to work with.',
            ['count' => $count]
        );
    }

    public function listen(): void
    {
        $this->checkAdapter();

        $this->logger->info('Start listening to the queue.');
        /** @psalm-suppress PossiblyNullReference */
        $this->adapter->subscribe(fn (MessageInterface $message) => $this->handle($message));
        $this->logger->info('Finish listening to the queue.');
    }

    public function status(string $id): JobStatus
    {
        $this->checkAdapter();

        /** @psalm-suppress PossiblyNullReference */
        return $this->adapter->status($id);
    }

    public function withAdapter(AdapterInterface $adapter): self
    {
        $new = clone $this;
        $new->adapter = $adapter;

        return $new;
    }

    protected function handle(MessageInterface $message): void
    {
        $this->worker->process($message, $this);
    }

    private function checkAdapter(): void
    {
        if ($this->adapter === null) {
            throw new AdapterNotConfiguredException();
        }
    }
}
