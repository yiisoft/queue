<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Support;

use Yiisoft\Queue\Message\SimpleMessage;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareInterface;

final class TestMiddleware implements PushMiddlewareInterface
{
    public function __construct(private readonly string $message = 'New middleware test data') {}

    public function processPush(MessageInterface $message, PushHandlerInterface $handler): MessageInterface
    {
        return new SimpleMessage('test', $this->message);
    }
}
