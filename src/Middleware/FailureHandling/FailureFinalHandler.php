<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\FailureHandling;

use Throwable;

/**
 * @internal
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
