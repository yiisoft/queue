<?php

use yii\di\Reference;

return [
    'queue' => [
        '__class' => \yii\queue\sync\Queue::class,
    ],
    \yii\queue\serializers\SerializerInterface::class => Reference::to('queue.serializer'),
    'queue.serializer' => [
        '__class' => \yii\queue\serializers\PhpSerializer::class,
    ],
];
