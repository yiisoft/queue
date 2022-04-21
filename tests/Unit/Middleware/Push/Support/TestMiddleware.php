<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Push\Support;

use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\Push\MessageHandlerPushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Yii\Queue\Middleware\Push\PushRequest;

final class TestMiddleware implements MiddlewarePushInterface
{
    public function processPush(PushRequest $request, MessageHandlerPushInterface $handler): PushRequest
    {
        return $request->withMessage(new Message('test', 'New middleware test data'));
    }
}
