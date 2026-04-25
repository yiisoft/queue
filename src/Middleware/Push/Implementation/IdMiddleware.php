<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push\Implementation;

use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;

/**
 * A middleware for message ID setting.
 */
final class IdMiddleware implements MiddlewarePushInterface
{
    public function processPush(MessageInterface $message, MessageHandlerPushInterface $handler): MessageInterface
    {
        $meta = $message->getMetadata();
        if (empty($meta[IdEnvelope::MESSAGE_ID_KEY])) {
            $message = new IdEnvelope($message, uniqid('yii3-message-', true));
        }

        return $handler->handlePush($message);
    }
}
