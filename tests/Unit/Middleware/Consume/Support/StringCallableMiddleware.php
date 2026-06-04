<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support;

use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;

final class StringCallableMiddleware
{
    public static function handle(ConsumeRequest $request): ConsumeRequest
    {
        return $request->withMessage(new GenericMessage('test', 'String callable data'));
    }
}
