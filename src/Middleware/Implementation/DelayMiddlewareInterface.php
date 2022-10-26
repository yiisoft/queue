<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation;

use Yiisoft\Yii\Queue\Middleware\Push\MiddlewarePushInterface;

interface DelayMiddlewareInterface extends MiddlewarePushInterface
{
    public function withDelay(float $delay): self;

    public function getDelay(): float;
}
