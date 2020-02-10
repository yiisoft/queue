<?php

use Yiisoft\Factory\Factory;
use Yiisoft\Yii\Queue\Events\AfterExecution;
use Yiisoft\Yii\Queue\Events\AfterExecutionInterface;
use Yiisoft\Yii\Queue\Events\AfterPush;
use Yiisoft\Yii\Queue\Events\AfterPushInterface;
use Yiisoft\Yii\Queue\Events\BeforeExecution;
use Yiisoft\Yii\Queue\Events\BeforeExecutionInterface;
use Yiisoft\Yii\Queue\Events\BeforePush;
use Yiisoft\Yii\Queue\Events\BeforePushInterface;
use Yiisoft\Yii\Queue\Events\JobFailure;
use Yiisoft\Yii\Queue\Events\JobFailureInterface;
use Yiisoft\Yii\Queue\Workers\WorkerInterface;

return [
    Factory::class => [
        '__construct' => [
            1 => [
                AfterExecutionInterface::class => AfterExecution::class,
                AfterPushInterface::class => AfterPush::class,
                BeforeExecutionInterface::class => BeforeExecution::class,
                BeforePushInterface::class => BeforePush::class,
                JobFailureInterface::class => JobFailure::class,
            ],
        ],
    ],
    \Psr\EventDispatcher\EventDispatcherInterface::class => \Yiisoft\EventDispatcher\Dispatcher::class,
    WorkerInterface::class => \Yiisoft\Yii\Queue\Workers\Worker::class,

];
