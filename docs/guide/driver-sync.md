Synchronous Driver
==================

Runs tasks synchronously in the same process.
It could be used when developing and debugging an application.

Configuration example:

```php
$eventDisptacher = $DIcontainer->get(\Psr\EventDispatcher\EventDispatcherInterface::class);
$logger = $DIcontainer->get(\Psr\Log\LoggerInterface::class);

$worker = $DIcontainer->get(\Yiisoft\Yii\Queue\Worker\WorkerInterface::class);
$loop = $DIcontainer->get(\Yiisoft\Yii\Queue\Cli\LoopInterface::class);
$driver = new Yiisoft\Yii\Queue\Driver\SynchronousDriver($loop, $worker);

$queue = new Yiisoft\Yii\Queue\Queue(
    $driver,
    $eventDisptacher,
    $worker,
    $loop,
    $logger
);
```
