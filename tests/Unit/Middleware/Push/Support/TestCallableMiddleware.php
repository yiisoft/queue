<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Push\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Push\PushRequest;

final class TestCallableMiddleware
{
    public function index(PushRequest $request): PushRequest
    {
        return $request->withMessage(new Message('New test data'));
    }
}
