<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Message\MessageInterface;

final class TestCallableMiddleware
{
    public function index(MessageInterface $message): MessageInterface
    {
        return new Message('test', 'New test data');
    }
}
