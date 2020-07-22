<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Yii\Queue\Payload\DelayablePayloadInterface;

class DelayableJob extends SimplePayload implements DelayablePayloadInterface
{
    public function getDelay(): int
    {
        return 1;
    }
}
