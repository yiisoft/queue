<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

interface FailureHandlerInterface
{
    public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest;
}
