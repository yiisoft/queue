<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use Yiisoft\Yii\Queue\Driver\SynchronousDriver;
use Yiisoft\Yii\Queue\Exception\JobNotSupportedException;
use Yiisoft\Yii\Queue\Job\DelayableJobInterface;
use Yiisoft\Yii\Queue\Job\PrioritisedJobInterface;
use Yiisoft\Yii\Queue\Job\RetryableJobInterface;
use Yiisoft\Yii\Queue\Tests\App\DelayableJob;
use Yiisoft\Yii\Queue\Tests\TestCase;

class ExceptionTest extends TestCase
{
    public function testJobNotSupported(): void
    {
        $jobClass = DelayableJob::class;
        $job = new DelayableJob();
        $driver = $this->container->get(SynchronousDriver::class);
        $driverClass = SynchronousDriver::class;
        $interfaces = DelayableJobInterface::class;

        $solution = <<<SOLUTION
            The given job $jobClass implements next system interfaces:
            $interfaces.

            Here is a list of all default interfaces which can be unsupported by different queue drivers:
            - DelayableJobInterface (allows to execute job with a delay)
            - PrioritisedJobInterface (is used to prioritize job execution)
            - RetryableJobInterface (allows to execute the job multiple times while it fails)

            The given driver $driverClass does not support one of them, or even more.
            The solution is in one of these:
            - Check which interfaces does $driverClass support and remove not supported interfaces from $jobClass.
            - Use another driver which supports all interfaces you need. Officially supported drivers are:
                - None yet :) Work is in progress.
            SOLUTION;

        $exception = new JobNotSupportedException($driver, $job);
        $this->assertStringContainsString(DelayableJob::class, $exception->getMessage());
        $this->assertStringContainsString($driverClass, $exception->getMessage());
        $this->assertEquals('Job is not supported by current queue driver', $exception->getName());
        $this->assertEquals($solution, $exception->getSolution());
    }
}
