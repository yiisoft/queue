<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Message\MessageInterface;

interface MiddlewarePushInterface
{
    public function processPush(MessageInterface $message, MessageHandlerPushInterface $handler): MessageInterface;
}
