<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

interface MessageHandlerInterface
{
    public function handle(Request $request): Request;
}
