<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Payload;

interface DelayablePayloadInterface extends PayloadInterface
{
    public function getDelay(): int;
}
