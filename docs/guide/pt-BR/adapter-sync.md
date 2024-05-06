Synchronous Adapter
==================

Run tasks synchronously in the same process. It could be used when developing and debugging an application.

Configuration example:

```php
$logger = $DIContainer->get(\Psr\Log\LoggerInterface::class);

$worker = $DIContainer->get(\Yiisoft\Queue\Worker\WorkerInterface::class);
$loop = $DIContainer->get(\Yiisoft\Queue\Cli\LoopInterface::class);
$adapter = new Yiisoft\Queue\Adapter\SynchronousAdapter($loop, $worker);

$queue = new Yiisoft\Queue\Queue(
    $adapter,
    $worker,
    $loop,
    $logger
);
```
