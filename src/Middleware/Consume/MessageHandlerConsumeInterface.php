<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Consume;

interface MessageHandlerConsumeInterface
{
    public function handleConsume(ConsumeRequest $request): ConsumeRequest;
}
