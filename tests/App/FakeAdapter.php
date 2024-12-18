<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\App;

use BackedEnum;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\ChannelNormalizer;
use Yiisoft\Queue\Enum\JobStatus;
use Yiisoft\Queue\Message\MessageInterface;

final class FakeAdapter implements AdapterInterface
{
    public array $pushMessages = [];
    public string $channel = 'default';

    public function push(MessageInterface $message): MessageInterface
    {
        $this->pushMessages[] = $message;

        return $message;
    }

    public function runExisting(callable $handlerCallback): void
    {
        //skip
    }

    public function status(string|int $id): JobStatus
    {
        //skip
    }

    public function subscribe(callable $handlerCallback): void
    {
        //skip
    }

    public function withChannel(string|BackedEnum $channel): AdapterInterface
    {
        $instance = clone $this;
        $instance->pushMessages = [];
        $instance->channel = ChannelNormalizer::normalize($channel);
        return $instance;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }
}
