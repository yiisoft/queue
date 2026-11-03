<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

interface PushMiddlewareInterface
{
    public function processPush(PushRequest $request, PushHandlerInterface $handler): PushRequest;
}
