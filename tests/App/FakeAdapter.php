<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\MessageInterface;

final class FakeAdapter implements AdapterInterface
{
    public array $pushMessages = [];
    public string $channel = 'default';

    public function push(MessageInterface $message): void
    {
        $this->pushMessages[] = $message;
    }

    public function runExisting(callable $handlerCallback): void
    {
        //skip
    }

    public function status(string $id): JobStatus
    {
        //skip
    }

    public function subscribe(callable $handlerCallback): void
    {
        //skip
    }

    public function withChannel(string $channel): AdapterInterface
    {
        $instance = clone $this;
        $instance->pushMessages = [];
        $instance->channel = $channel;

        return $instance;
    }
}
