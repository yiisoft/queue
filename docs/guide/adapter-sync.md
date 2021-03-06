Synchronous Adapter
==================

Run tasks synchronously in the same process. It could be used when developing and debugging an application.

Configuration example:

```php
$eventDispatcher = $DIContainer->get(\Psr\EventDispatcher\EventDispatcherInterface::class);
$logger = $DIContainer->get(\Psr\Log\LoggerInterface::class);

$worker = $DIContainer->get(\Yiisoft\Yii\Queue\Worker\WorkerInterface::class);
$loop = $DIContainer->get(\Yiisoft\Yii\Queue\Cli\LoopInterface::class);
$adapter = new Yiisoft\Yii\Queue\Adapter\SynchronousAdapter($loop, $worker);

$queue = new Yiisoft\Yii\Queue\Queue(
    $adapter,
    $eventDispatcher,
    $worker,
    $loop,
    $logger
);
```
