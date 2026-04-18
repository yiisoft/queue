<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;

final class TestMiddleware implements MiddlewarePushInterface
{
    public function __construct(private readonly string $message = 'New middleware test data') {}

    public function processPush(MessageInterface $message, MessageHandlerPushInterface $handler): MessageInterface
    {
        return new Message('test', $this->message);
    }
}
