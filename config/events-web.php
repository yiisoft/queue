<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Adapter\SynchronousAdapter;
use Yiisoft\Queue\QueueInterface;

/* @var array $params */

return [
    \Yiisoft\Yii\Http\Event\AfterRequest::class => [
        function (AdapterInterface $adapter, ContainerInterface $container) {
            if ($adapter instanceof SynchronousAdapter) {
                $container->get(QueueInterface::class)->run(0);
            }
        }
    ],
];
