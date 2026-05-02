<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Message\MessageInterface;

interface PushMiddlewareInterface
{
    public function processPush(MessageInterface $message, PushHandlerInterface $handler): MessageInterface;
}
