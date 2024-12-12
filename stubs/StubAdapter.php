<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use BackedEnum;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\ChannelNormalizer;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;

/**
 * Stub adapter that does nothing. Job status is always "done".
 */
final class StubAdapter implements AdapterInterface
{
    private string $channelName;

    public function __construct(
        string|BackedEnum $channelName = QueueInterface::DEFAULT_CHANNEL_NAME
    ) {
        $this->channelName = ChannelNormalizer::normalize($channelName);
    }

    public function runExisting(callable $handlerCallback): void
    {
    }

    public function status(int|string $id): JobStatus
    {
        return JobStatus::done();
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
        $new->channelName = ChannelNormalizer::normalize($channel);
        return $new;
    }

    public function getChannelName(): string
    {
        return $this->channelName;
    }
}
