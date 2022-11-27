<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\FailureHandling;

interface MessageFailureHandlerInterface
{
    public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest;
}
