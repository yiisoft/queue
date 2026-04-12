<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use BackedEnum;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\MessageStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\QueueInterface;
use Yiisoft\Queue\StringNormalizer;

/**
 * Stub queue that does nothing. Message status is always "done".
 *
 * @template T of AdapterInterface
 */
final class StubQueue implements QueueInterface
{
    /**
     * @param T|null $adapter
     */
    public function __construct(
        private ?AdapterInterface $adapter = null,
        private string $name = 'default'
    ) {
    }

    public function push(
        MessageInterface $message,
        string|array|callable|MiddlewarePushInterface ...$middlewareDefinitions
    ): MessageInterface {
        return $message;
    }

    public function run(int $max = 0): int
    {
        return 0;
    }

    public function listen(): void
    {
    }

    public function status(int|string $id): MessageStatus
    {
        return MessageStatus::DONE;
    }

    /**
     * @return T|null
     */
    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * @param T $adapter
     * @return static<T>
     */
    public function withAdapter(AdapterInterface $adapter, string|BackedEnum|null $queueName = null): static
    {
        $new = clone $this;
        $new->adapter = $adapter;

        if ($queueName !== null) {
            $new->name = StringNormalizer::normalize($queueName);
        }

        return $new;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
