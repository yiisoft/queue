<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

interface FailureMiddlewareInterface
{
    public function processFailure(FailureHandlingRequest $request, FailureHandlerInterface $handler): FailureHandlingRequest;
}
