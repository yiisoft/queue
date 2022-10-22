<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Implementation\FailureStrategy\Dispatcher;

interface DispatcherFactoryInterface
{
    public function get(string $payloadName): DispatcherInterface;
}
