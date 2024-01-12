<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\FailureHandling\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\FailureHandling\FailureHandlingRequest;

final class TestCallableMiddleware
{
    public function index(FailureHandlingRequest $request): FailureHandlingRequest
    {
        return $request->withMessage(new Message('test', 'New test data'));
    }
}
