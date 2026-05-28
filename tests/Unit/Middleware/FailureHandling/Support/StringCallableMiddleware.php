<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Support;

use Yiisoft\Queue\Message\SimpleMessage;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;

final class StringCallableMiddleware
{
    public static function handle(FailureHandlingRequest $request): FailureHandlingRequest
    {
        return $request->withMessage(new SimpleMessage('test', 'String callable data'));
    }
}
