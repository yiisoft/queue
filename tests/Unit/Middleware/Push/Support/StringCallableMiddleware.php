<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Push\PushRequest;

final class StringCallableMiddleware
{
    public static function handle(PushRequest $request): PushRequest
    {
        return $request->withMessage(new Message('test', 'String callable data'));
    }
}
