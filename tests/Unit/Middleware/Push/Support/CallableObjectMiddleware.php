<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Support;

use Yiisoft\Queue\Message\SimpleMessage;
use Yiisoft\Queue\Message\MessageInterface;

final class CallableObjectMiddleware
{
    public function __invoke(MessageInterface $message): MessageInterface
    {
        return new SimpleMessage('test', 'Callable object data');
    }
}
