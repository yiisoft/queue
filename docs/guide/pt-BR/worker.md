# Configuration

To use a worker, you should resolve its dependencies (e.g. through DI container) and define handlers for each message
which will be consumed by this worker;

Handlers are callables indexed by payload names. When a message is consumed from the queue, a callable associated with
its payload name is called.

## Handler format

Handler can be any callable with a couple of additions:

- If handler is provided as an array of two strings, it will be treated as a DI container service id and its method.
  E.g. `[ClassName::class, 'handle']` will be resolved to:
  ```php 
  $container
      ->get(ClassName::class)
      ->handle();
  ```
- An `Injector` is used to call the handlers. This means you can define handlers as closures with their own dependencies
  which will be resolved with DI container. In the example below you can see a closure in which `message` will be taken
  from the queue and `ClientInterface` will be resolved via DI container.
  
  ```php
  'payloadName' => fn (MessageInterface $message, ClientInterface $client) => $client->send($message->getPayloadData()),
  ```

  ```php
  $handlers = [
      'simple' => fn() => 'someWork',
      'anotherHandler' => [QueueHandlerCollection::class, 'methodName']
  ];
  $worker = new Worker(
      $handlers,
      new \Psr\Log\NullLogger(),
      new \Yiisoft\Injector\Injector($DIContainer),
      $DIContainer
  );
  ```

## Starting Workers

### Supervisor

[Supervisor](http://supervisord.org) is a process monitor for Linux. It automatically starts console processes.
On Ubuntu or Debian it can be installed with the following command:

```sh
sudo apt-get install supervisor
```

Supervisor config files are usually available in `/etc/supervisor/conf.d`. You can create any number of
config files there.

Here's an example:

```conf
[program:yii-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/my_project/yii queue:listen --verbose=1 --color=0
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/my_project/log/yii-queue-worker.log
```

In this case Supervisor should start 4 `queue:listen` workers. The worker output will be written
to the specified log file.

For more info about Supervisor's configuration and usage see its [documentation](http://supervisord.org).

### Systemd

Systemd is another init system used on Linux to bootstrap the user space. To configure workers startup
using systemd, create a config file named `yii-queue@.service` in `/etc/systemd/system` with
the following content:

```ini
[Unit]
Description=Yii Queue Worker %I
After=network.target
# the following two lines only apply if your queue backend is mysql
# replace this with the service that powers your backend
After=mysql.service
Requires=mysql.service

[Service]
User=www-data
Group=www-data
ExecStart=/usr/bin/php /var/www/my_project/yii queue:listen --verbose
Restart=on-failure

[Install]
WantedBy=multi-user.target
```

You need to reload systemd in order to re-read its configuration:

```shell
systemctl daemon-reload
```

Set of commands to control workers:

```shell
# To start two workers
systemctl start yii-queue@1 yii-queue@2

# To get the status of running workers
systemctl status "yii-queue@*"

# To stop a specific worker
systemctl stop yii-queue@2

# To stop all running workers
systemctl stop "yii-queue@*"

# To start two workers at system boot
systemctl enable yii-queue@1 yii-queue@2
```

To learn all features of systemd, check its [documentation](https://freedesktop.org/wiki/Software/systemd/#manualsanddocumentationforusersandadministrators).

### Cron

You can also start workers using cron that executes `queue:run` command.

Config example:

```shell
* * * * * /usr/bin/php /var/www/my_project/yii queue:run
```

In this case cron will run the command every minute.
