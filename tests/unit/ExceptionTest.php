<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use Yiisoft\Yii\Queue\Driver\SynchronousDriver;
use Yiisoft\Yii\Queue\Exception\PayloadNotSupportedException;
use Yiisoft\Yii\Queue\Payload\DelayablePayloadInterface;
use Yiisoft\Yii\Queue\Tests\App\DelayablePayload;
use Yiisoft\Yii\Queue\Tests\TestCase;

class ExceptionTest extends TestCase
{
    public function testJobNotSupported(): void
    {
        $payload = new DelayablePayload();
        $payloadName = $payload->getName();
        $driver = $this->container->get(SynchronousDriver::class);
        $driverClass = SynchronousDriver::class;
        $interfaces = DelayablePayloadInterface::class;

        $solution = <<<SOLUTION
            The given payload $payloadName implements next system interfaces:
            $interfaces.

            Here is a list of all default interfaces which can be unsupported by different queue drivers:
            - DelayablePayloadInterface (allows to execute job with a delay)
            - PrioritisedPayloadInterface (is used to prioritize job execution)
            - AttemptsRestrictedPayloadInterface (allows to execute the job multiple times while it fails)

            The given driver $driverClass does not support one of them, or even more.
            The solution is in one of these:
            - Check which interfaces does $driverClass support and remove not supported interfaces from $payloadName.
            - Use another driver which supports all interfaces you need. Officially supported drivers are:
                - yiisoft/yii-queue-amqp
            SOLUTION;

        $exception = new PayloadNotSupportedException($driver, $payload);
        $this->assertStringContainsString($payload->getName(), $exception->getMessage());
        $this->assertStringContainsString($driverClass, $exception->getMessage());
        $this->assertEquals('Payload is not supported by current queue driver', $exception->getName());
        $this->assertEquals($solution, $exception->getSolution());
    }
}
