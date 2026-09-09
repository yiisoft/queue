<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push\Implementation;

use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareInterface;

/**
 * A middleware for message ID setting.
 */
final class IdMiddleware implements PushMiddlewareInterface
{
    public function processPush(PushRequest $request, PushHandlerInterface $handler): PushRequest
    {
        $message = $request->getMessage();
        $envelope = IdEnvelope::fromMessage($message);

        if ($envelope->getId() === null) {
            return $handler->handlePush(
                $request->withMessage(new IdEnvelope($message, uniqid('yii3-message-', true))),
            );
        }

        return $handler->handlePush($request);
    }
}
