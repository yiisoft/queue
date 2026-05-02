<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

interface ConsumeMiddlewareInterface
{
    public function processConsume(ConsumeRequest $request, ConsumeHandlerInterface $handler): ConsumeRequest;
}
