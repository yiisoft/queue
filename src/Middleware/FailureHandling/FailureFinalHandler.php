<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\FailureHandling;

use Throwable;

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
        throw $request->getException();
    }
}
