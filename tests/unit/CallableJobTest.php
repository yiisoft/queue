<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\unit;

use stdClass;
use Yiisoft\Serializer\SerializerInterface;
use Yiisoft\Yii\Queue\Job\CallableJob;
use Yiisoft\Yii\Queue\Tests\TestCase;

class CallableJobTest extends TestCase
{
    private bool $jobExecuted;

    public function testExecuteLambda(): void
    {
        $result = false;
        $callable = static function () use (&$result) {
            $result = true;
        };

        $job = new CallableJob($callable);
        $job->execute();

        $this->assertTrue($result);
    }

    public function testExecuteArrow(): void
    {
        $result = new stdClass();
        $result->executed = false;
        $callable = static fn () => $result->executed = true;

        $job = new CallableJob($callable);
        $job->execute();

        $this->assertTrue($result->executed);
    }

    public function testExecuteArray(): void
    {
        $this->jobExecuted = false;
        $callable = [$this, 'jobExecutor'];

        $job = new CallableJob($callable);
        $job->execute();

        $this->assertTrue($this->jobExecuted);
    }

    public function jobExecutor(): void
    {
        $this->jobExecuted = true;
    }
}
