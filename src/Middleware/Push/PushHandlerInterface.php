<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

interface PushHandlerInterface
{
    public function handlePush(PushRequest $request): PushRequest;
}
