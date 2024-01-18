<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

interface MiddlewarePushInterface
{
    public function processPush(PushRequest $request, MessageHandlerPushInterface $handler): PushRequest;
}
