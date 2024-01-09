<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

interface MessageHandlerConsumeInterface
{
    public function handleConsume(ConsumeRequest $request): ConsumeRequest;
}
