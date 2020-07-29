<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Payload;

interface PrioritisedPayloadInterface extends PayloadInterface
{
    public function getPriority(): int;
}
