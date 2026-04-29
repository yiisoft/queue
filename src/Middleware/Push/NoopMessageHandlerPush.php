<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;

/**
 * @internal
 */
final class NoopMessageHandlerPush implements MessageHandlerPushInterface
{
    public function handlePush(MessageInterface $message): MessageInterface
    {
        return $message;
    }
}
