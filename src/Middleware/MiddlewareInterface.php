<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

interface MiddlewareInterface
{
    public function process(Request $request, MessageHandlerInterface $handler): Request;
}
