<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Yii\Queue\Message\Behaviors\BehaviorInterface;

class DummyBehavior implements BehaviorInterface
{
    public function getConstructorParameters(): array
    {
        return [];
    }
}
