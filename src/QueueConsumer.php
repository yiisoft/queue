<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use BackedEnum;
use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\ScopedLoopInterface;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Worker\WorkerInterface;

/** Consumes messages for one logical queue. */
final class QueueConsumer implements QueueConsumerInterface
{
    private string $queueName;

    public function __construct(
        private readonly WorkerInterface $worker,
        private readonly LoopInterface $loop,
        private readonly LoggerInterface $logger,
        private readonly ?AdapterInterface $adapter = null,
        string|BackedEnum $queueName = DefaultQueue::NAME,
    ) {
        $this->queueName = StringNormalizer::normalize($queueName);
    }

    public function run(int $max = 0): int
    {
        if ($this->adapter === null) {
            $this->logger->debug('Queue is in synchronous mode (no adapter). Messages are processed on push. run() does nothing.');
            return 0;
        }
        $this->logger->debug('Start processing queue messages.');
        $count = 0;
        $this->startLoop();
        try {
            $this->adapter->runExisting(function (MessageInterface $message) use (&$count, $max): bool {
                if (($max > 0 && $count >= $max) || !$this->handle($message)) {
                    return false;
                }
                $count++;
                return true;
            });
        } finally {
            $this->finishLoop();
        }
        $this->logger->info('Processed {count} queue messages.', ['count' => $count]);
        return $count;
    }

    public function listen(): void
    {
        if ($this->adapter === null) {
            $this->logger->info('Cannot listen without an adapter. Queue is in synchronous mode.');
            return;
        }
        $this->logger->info('Start listening to the queue.');
        $this->startLoop();
        try {
            $this->adapter->subscribe(fn(MessageInterface $message): bool => $this->handle($message));
        } finally {
            $this->finishLoop();
        }
        $this->logger->info('Finish listening to the queue.');
    }

    private function startLoop(): void
    {
        if ($this->loop instanceof ScopedLoopInterface) {
            $this->loop->start();
        }
    }

    private function finishLoop(): void
    {
        if ($this->loop instanceof ScopedLoopInterface) {
            $this->loop->finish();
        }
    }

    private function handle(MessageInterface $message): bool
    {
        if (!$this->loop->canContinue()) {
            return false;
        }

        $this->worker->process($message, $this->queueName);
        return true;
    }
}
