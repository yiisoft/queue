<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Push;

interface MiddlewarePushInterface
{
    public function processPush(PushRequest $request, MessageHandlerPushInterface $handler): PushRequest;
}
