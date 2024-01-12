<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

interface MiddlewareFailureInterface
{
    public function processFailure(FailureHandlingRequest $request, MessageFailureHandlerInterface $handler): FailureHandlingRequest;
}
