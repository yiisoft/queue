<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Integration\Support;

use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\PushRequest;

final class TestMiddleware implements MiddlewarePushInterface
{
    public function __construct(private string $stage)
    {
    }

    public function processPush(PushRequest $request, MessageHandlerPushInterface $handler): PushRequest
    {
        $message = $request->getMessage();
        $stack = $message->getData();
        $stack[] = $this->stage;
        $messageNew = new Message($message->getHandlerName(), $stack);

        return $handler->handlePush($request->withMessage($messageNew));
    }
}
