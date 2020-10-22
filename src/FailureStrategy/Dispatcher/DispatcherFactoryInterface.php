<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\FailureStrategy\Dispatcher;

interface DispatcherFactoryInterface
{
    public function get(string $payloadName): DispatcherInterface;
}
