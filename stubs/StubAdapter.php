<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use BackedEnum;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\ChannelNormalizer;
use Yiisoft\Queue\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;

/**
 * Stub adapter that does nothing. Job status is always "done".
 */
final class StubAdapter implements AdapterInterface
{
    private string $channel;

    public function __construct(
        string|BackedEnum $channel = QueueInterface::DEFAULT_CHANNEL
    ) {
        $this->channel = ChannelNormalizer::normalize($channel);
    }

    public function runExisting(callable $handlerCallback): void
    {
    }

    public function status(int|string $id): JobStatus
    {
        return JobStatus::DONE;
    }

    public function push(MessageInterface $message): MessageInterface
    {
        return $message;
    }

    public function subscribe(callable $handlerCallback): void
    {
    }

    public function withChannel(string|BackedEnum $channel): AdapterInterface
    {
        $new = clone $this;
        $new->channel = ChannelNormalizer::normalize($channel);
        return $new;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }
}
