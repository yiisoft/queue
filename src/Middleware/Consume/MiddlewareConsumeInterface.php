<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Consume;

interface MiddlewareConsumeInterface
{
    public function processConsume(ConsumeRequest $request, MessageHandlerConsumeInterface $handler): ConsumeRequest;
}
