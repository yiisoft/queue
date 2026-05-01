<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Stubs;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\Implementation\DelayMiddlewareInterface;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;

final class StubDelayMiddleware implements DelayMiddlewareInterface
{
    public function withDelay(float $seconds): DelayMiddlewareInterface
    {
        return $this;
    }

    public function processPush(MessageInterface $message, MessageHandlerPushInterface $handler): MessageInterface
    {
        return $handler->handlePush($message);
    }
}
