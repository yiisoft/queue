Synchronous Driver
==================

Run tasks synchronously in the same process.
It could be used when developing and debugging an application.

Configuration example:

```php
$eventDispatcher = $DContainer->get(\Psr\EventDispatcher\EventDispatcherInterface::class);
$logger = $DIContainer->get(\Psr\Log\LoggerInterface::class);

$worker = $DIContainer->get(\Yiisoft\Yii\Queue\Worker\WorkerInterface::class);
$loop = $DIContainer->get(\Yiisoft\Yii\Queue\Cli\LoopInterface::class);
$driver = new Yiisoft\Yii\Queue\Driver\SynchronousDriver($loop, $worker);

$queue = new Yiisoft\Yii\Queue\Queue(
    $driver,
    $eventDispatcher,
    $worker,
    $loop,
    $logger
);
```
