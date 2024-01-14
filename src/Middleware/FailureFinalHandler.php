<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

use Throwable;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;
use Yiisoft\Queue\Middleware\FailureHandling\MessageFailureHandlerInterface;

/**
 * @internal Internal package class, please don't use it directly
 */
final class FailureFinalHandler implements MessageFailureHandlerInterface
{
    /**
     * @throws Throwable
     */
    public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest
    {
        // TODO: Add tests
        throw $request->getException() ?? new \RuntimeException('Message processing failed.');
    }
}
