<?php

use yii\di\Reference;

return [
    'queue' => [
        '__class' => \Yiisoft\Yii\Queue\Drivers\Sync\Queue::class,
    ],
    \Yiisoft\Yii\Queue\Serializers\SerializerInterface::class => Reference::to('queue.serializer'),
    'queue.serializer'                                => [
        '__class' => \Yiisoft\Yii\Queue\Serializers\PhpSerializer::class,
    ],
];
