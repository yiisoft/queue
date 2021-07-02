<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use Yiisoft\Yii\Queue\Message\Behaviors\DelayBehavior;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class DelayBehaviorTest extends TestCase
{
    public function testRestorationFromData(): void
    {
        $delay = 5;
        $behavior = new DelayBehavior($delay);
        $behavior2 = DelayBehavior::fromData($behavior->getSerializableData());

        self::assertEquals($behavior->getDelay(), $behavior2->getDelay());
    }

    public function testRestorationFromSerializedData(): void
    {
        $delay = 5;
        $behavior = new DelayBehavior($delay);
        $behavior2 = DelayBehavior::fromData(json_decode(json_encode($behavior->getSerializableData()), true));

        self::assertEquals($behavior->getDelay(), $behavior2->getDelay());
    }

    public function testGetDelay(): void
    {
        $delay = 5;
        $behavior = new DelayBehavior($delay);

        self::assertEquals($delay, $behavior->getDelay());
    }
}
