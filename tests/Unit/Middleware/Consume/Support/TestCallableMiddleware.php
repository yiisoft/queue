<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support;

use Yiisoft\Queue\Message\SimpleMessage;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;

final class TestCallableMiddleware
{
    public function index(ConsumeRequest $request): ConsumeRequest
    {
        return $request->withMessage(new SimpleMessage('test', 'New test data'));
    }
}
