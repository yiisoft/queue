<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit\Middleware\Push\Support;

use Yiisoft\Yii\Queue\Message\Message;
use Yiisoft\Yii\Queue\Middleware\Push\PushRequest;

final class TestCallableMiddleware
{
    public function index(PushRequest $request): PushRequest
    {
        return $request->withMessage(new Message('test', 'New test data'));
    }
}
