<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Support;

use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Push\PushRequest;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;

final class StringCallableMiddleware
{
    public static function handle(PushRequest $request, PushHandlerInterface $handler): PushRequest
    {
        return $request->withMessage(new GenericMessage('test', 'String callable data'));
    }
}
