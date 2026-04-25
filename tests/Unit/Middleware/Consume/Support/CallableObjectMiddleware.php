<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support;

use Yiisoft\Queue\Message\Message;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;

final class CallableObjectMiddleware
{
    public function __invoke(ConsumeRequest $request): ConsumeRequest
    {
        return $request->withMessage(new Message('test', 'Callable object data'));
    }
}
