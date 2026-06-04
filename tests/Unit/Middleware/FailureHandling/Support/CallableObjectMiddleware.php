<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Support;

use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;

final class CallableObjectMiddleware
{
    public function __invoke(FailureHandlingRequest $request): FailureHandlingRequest
    {
        return $request->withMessage(new GenericMessage('test', 'Callable object data'));
    }
}
