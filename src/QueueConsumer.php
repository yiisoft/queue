<?php

declare(strict_types=1);

namespace Yiisoft\Queue;

use BackedEnum;
use Psr\Log\LoggerInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Provider\QueueConsumerProviderInterface;
use Yiisoft\Queue\Worker\WorkerInterface;

/** Consumes messages for one logical queue. */
final class QueueConsumer implements QueueConsumerInterface
{
    private string $name;

    public function __construct(
        private readonly WorkerInterface $worker,
        private readonly LoopInterface $loop,
        private readonly LoggerInterface $logger,
        private readonly ?AdapterInterface $adapter = null,
        string|BackedEnum $name = QueueConsumerProviderInterface::DEFAULT_QUEUE,
    ) {
        $this->name = StringNormalizer::normalize($name);
    }

    public function run(int $max = 0): int
    {
        if ($this->adapter === null) {
            $this->logger->debug('Queue is in synchronous mode (no adapter). Messages are processed on push. run() does nothing.');
            return 0;
        }
        $this->logger->debug('Start processing queue messages.');
        $count = 0;
        $this->adapter->runExisting(function (MessageInterface $message) use (&$count, $max): bool {
            if (($max > 0 && $count >= $max) || !$this->handle($message)) {
                return false;
            }
            $count++;
            return true;
        });
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
        $this->adapter->subscribe(fn(MessageInterface $message): bool => $this->handle($message));
        $this->logger->info('Finish listening to the queue.');
    }

    private function handle(MessageInterface $message): bool
    {
        $this->worker->process($message, $this->name);
        return $this->loop->canContinue();
    }
}
