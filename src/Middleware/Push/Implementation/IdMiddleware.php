<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push\Implementation;

use Yiisoft\Queue\Message\IdEnvelope;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Middleware\Push\MessageHandlerPushInterface;

/**
 * A middleware for message ID setting.
 */
final class IdMiddleware implements MiddlewarePushInterface
{
    public function processPush(PushRequest $request, MessageHandlerPushInterface $handler): PushRequest
    {
        $meta = $request->getMessage()->getMetadata();
        if (empty($meta[IdEnvelope::MESSAGE_ID_KEY])) {
            $request = $request->withMessage(new IdEnvelope($request->getMessage(), uniqid('yii3-message-', true)));
        }

        return $handler->handlePush($request);
    }
}
