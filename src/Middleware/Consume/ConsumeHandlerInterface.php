<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

interface ConsumeHandlerInterface
{
    public function handleConsume(ConsumeRequest $request): ConsumeRequest;
}
