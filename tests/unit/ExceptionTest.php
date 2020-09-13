<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use Yiisoft\Yii\Queue\Driver\SynchronousDriver;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Payload\DelayablePayloadInterface;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;
use Yiisoft\Yii\Queue\Payload\PrioritisedPayloadInterface;
use Yiisoft\Yii\Queue\Tests\App\DelayablePayload;
use Yiisoft\Yii\Queue\Tests\App\DummyInterface;
use Yiisoft\Yii\Queue\Tests\TestCase;

final class ExceptionTest extends TestCase
{
    public function testJobNotSupported(): void
    {
        $payload = $this->getPayload();
        $driver = $this->getDriver();
        $driverClass = get_class($driver);

        $exception = new PayloadNotSupportedException($driver, $payload);
        $this->assertStringContainsString(
            $payload->getName(),
            $exception->getMessage(),
            'Payload name must be included'
        );
        $this->assertStringContainsString(
            $driverClass,
            $exception->getMessage(),
            'Driver class must be included'
        );
        $this->assertStringContainsString(
            DelayablePayloadInterface::class,
            $exception->getSolution(),
            'DelayablePayloadInterface must be included to the exception message as it is a default interface and the payload implements it'
        );
        $this->assertStringNotContainsString(
            PrioritisedPayloadInterface::class,
            $exception->getSolution(),
            'PrioritisedPayloadInterface must not be included as it is not implemented in the payload'
        );
        $this->assertStringNotContainsString(
            DummyInterface::class,
            $exception->getMessage(),
            'DummyInterface must not be included as it is not a part of yii-queue package yet it is implemented in the payload'
        );
        $this->assertEquals("Payload is not supported by current queue driver", $exception->getName());
    }

    private function getPayload(): PayloadInterface
    {
        return new class() extends DelayablePayload implements DummyInterface {
        };
    }
}
