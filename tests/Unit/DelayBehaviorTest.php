<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use Yiisoft\Yii\Queue\Message\Behaviors\DelayBehavior;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class DelayBehaviorTest extends TestCase
{
    public function testConstructorParameters(): void
    {
        $delay = 5;
        $behavior = new DelayBehavior($delay);

        self::assertEquals([$delay], $behavior->getConstructorParameters());
    }

    public function testConstructorParametersUsage(): void
    {
        $delay = 5;
        $behavior = new DelayBehavior($delay);
        $behavior2 = new DelayBehavior(...$behavior->getConstructorParameters());

        self::assertEquals($behavior->getDelay(), $behavior2->getDelay());
    }

    public function testGetDelay(): void
    {
        $delay = 5;
        $behavior = new DelayBehavior($delay);

        self::assertEquals($delay, $behavior->getDelay());
    }
}
