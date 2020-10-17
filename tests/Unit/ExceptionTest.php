<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\Unit;

use Yiisoft\Yii\Queue\Exception\BehaviorNotSupportedException;
use Yiisoft\Yii\Queue\Message\Behaviors\DelayBehavior;
use Yiisoft\Yii\Queue\Message\Behaviors\PriorityBehavior;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class ExceptionTest extends TestCase
{
    public function testJobNotSupported(): void
    {
        $driver = $this->getDriver();
        $driverClass = get_class($driver);
        $behavior = new DelayBehavior(2);

        $exception = new BehaviorNotSupportedException($driver, $behavior);

        self::assertStringContainsString(
            DelayBehavior::class,
            $exception->getMessage(),
            'Payload name must be included'
        );
        self::assertStringContainsString(
            $driverClass,
            $exception->getMessage(),
            'Driver class must be included'
        );
        self::assertStringContainsString(
            DelayBehavior::class,
            $exception->getSolution(),
            'DelayablePayloadInterface must be included to the exception message as it is a default interface and the payload implements it'
        );
        self::assertStringNotContainsString(
            PriorityBehavior::class,
            $exception->getSolution(),
            'PriorityBehavior must not be included as it is not implemented in the payload'
        );
        self::assertEquals("Behavior is not supported by current queue driver", $exception->getName());
    }
}
