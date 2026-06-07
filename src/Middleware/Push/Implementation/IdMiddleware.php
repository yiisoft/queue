<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push\Implementation;

use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareInterface;

/**
 * A middleware for message ID setting.
 */
final class IdMiddleware implements PushMiddlewareInterface
{
    public function processPush(MessageInterface $message, PushHandlerInterface $handler): MessageInterface
    {
        $envelope = IdEnvelope::fromMessage($message);

        if ($envelope->getId() === null) {
            return $handler->handlePush(
                new IdEnvelope($message, uniqid('yii3-message-', true)),
            );
        }

        return $handler->handlePush($message);
    }
}
