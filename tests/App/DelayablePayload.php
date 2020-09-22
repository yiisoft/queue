<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Yii\Queue\Payload\DelayablePayloadInterface;

class DelayablePayload extends SimplePayload implements DelayablePayloadInterface
{
    protected string $name = 'delayable';

    public function getDelay(): int
    {
        return 1;
    }
}
