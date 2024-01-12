<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

interface MessageHandlerPushInterface
{
    public function handlePush(PushRequest $request): PushRequest;
}
