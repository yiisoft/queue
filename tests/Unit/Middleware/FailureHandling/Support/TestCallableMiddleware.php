<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\FailureHandling\Support;

use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\FailureHandling\FailureHandlingRequest;

final class TestCallableMiddleware
{
    public function index(FailureHandlingRequest $request): FailureHandlingRequest
    {
        return $request->withMessage(new Message('test', 'New test data'));
    }
}
