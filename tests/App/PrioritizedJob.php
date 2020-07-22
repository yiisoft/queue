<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Yii\Queue\Payload\PrioritisedPayloadInterface;

class PrioritizedJob extends SimplePayload implements PrioritisedPayloadInterface
{
    public function getPriority(): int
    {
        return 1;
    }
}
