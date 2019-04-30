<?php

return [
    'aliases' => [
        '@runtime' => dirname(__DIR__) . '/tests/runtime',
    ],
    'syncQueue' => [
        '__class' => \Yiisoft\Yii\Queue\Drivers\Sync\Queue::class,
    ],
];
