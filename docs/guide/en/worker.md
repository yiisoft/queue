# Worker

To use a worker, you should resolve its dependencies (e.g., through DI container) and [define handlers](message-handler.md) for each message that will be consumed by this worker.

## Starting Workers

To start a worker, you should run the console commands such as `queue:run`, `queue:listen`, and `queue:listen-all`. See [Console commands](console-commands.md) for details.

Below are three popular ways to run consumers in production so that they keep running in memory and are automatically restarted if needed.

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

For more info about Supervisor's configuration and usage, see its [documentation](http://supervisord.org).

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

You need to reload systemd to re-read its configuration:

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

In this case, cron will run the command every minute.
