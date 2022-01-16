<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Yii\Queue\Adapter\AdapterInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\MessageInterface;

final class FakeAdapter implements AdapterInterface
{
    public array $pushMessages = [];

    public function push(MessageInterface $message): void
    {
        $this->pushMessages[] = $message;
    }

    public function runExisting(callable $callback): void
    {
        //skip
    }

    public function status(string $id): JobStatus
    {
        //skip
    }

    public function subscribe(callable $handler): void
    {
        //skip
    }

    public function withChannel(string $channel): AdapterInterface
    {
        //skip
    }
}
