<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Request;

final class TestCallableMiddleware
{
    public function index(Request $request): Request
    {
        return $request->withMessage(new Message('New test data'));
    }
}
