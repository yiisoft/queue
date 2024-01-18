<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\FailureHandling;

interface MessageFailureHandlerInterface
{
    public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest;
}
