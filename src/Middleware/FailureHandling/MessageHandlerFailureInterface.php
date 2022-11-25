<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\FailureHandling;

interface MessageHandlerFailureInterface
{
    public function handleFailure(FailureHandlingRequest $request): FailureHandlingRequest;
}
