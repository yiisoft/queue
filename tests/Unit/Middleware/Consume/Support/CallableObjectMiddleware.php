<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Tests\Unit\Middleware\Consume\Support;

use Yiisoft\Queue\Message\GenericMessage;
use Yiisoft\Queue\Middleware\Consume\ConsumeRequest;

final class CallableObjectMiddleware
{
    public function __invoke(ConsumeRequest $request): ConsumeRequest
    {
        return $request->withMessage(new GenericMessage('test', 'Callable object data'));
    }
}
