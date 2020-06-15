<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Tests\App;

use Yiisoft\Yii\Queue\Event\AfterExecution;
use Yiisoft\Yii\Queue\Event\AfterPush;
use Yiisoft\Yii\Queue\Event\BeforeExecution;
use Yiisoft\Yii\Queue\Event\BeforePush;
use Yiisoft\Yii\Queue\Event\JobFailure;

interface EventManager
{
    public function beforePushHandler(BeforePush $event);
    public function afterPushHandler(AfterPush $event);
    public function beforeExecutionHandler(BeforeExecution $event);
    public function afterExecutionHandler(AfterExecution $event);
    public function jobFailureHandler(JobFailure $event);
}
