<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use Yiisoft\Yii\Queue\Message\Behaviors\PriorityBehavior;
use Yiisoft\Yii\Queue\Tests\TestCase;

class PriorityBehaviorTest extends TestCase
{
    public function testConstructorParameters(): void
    {
        $priority = 5;
        $behavior = new PriorityBehavior($priority);

        self::assertEquals([$priority], $behavior->getConstructorParameters());
    }

    public function testConstructorParametersUsage(): void
    {
        $priority = 5;
        $behavior = new PriorityBehavior($priority);
        $behavior2 = new PriorityBehavior(...$behavior->getConstructorParameters());

        self::assertEquals($behavior->getPriority(), $behavior2->getPriority());
    }

    public function testGetPriority(): void
    {
        $priority = 5;
        $behavior = new PriorityBehavior($priority);

        self::assertEquals($priority, $behavior->getPriority());
    }
}
