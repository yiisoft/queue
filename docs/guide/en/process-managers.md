# Running workers in production (systemd, Supervisor, cron)

A queue worker is a long-running process.

Running it manually from a terminal is fine for development, but in production you should manage workers with a process manager (for example, `systemd` or Supervisor).

Without a process manager, the following issues are common:

- Workers do not start after a server reboot or deployment.
- A single unexpected crash (PHP fatal error, segfault in an extension, out-of-memory) stops processing until someone notices and starts the worker again.
- It is difficult to run and control multiple worker processes consistently.
- Stopping workers safely during deploy becomes error-prone (for example, terminating a worker in the middle of a message).
- Logs are scattered across terminals and are hard to collect and inspect.

A process manager is responsible for:

- Starting workers on boot.
- Restarting workers on failure.
- Providing a standard way to start/stop/restart a group of workers.
- Centralizing logs and operational commands.

The most common process managers are `systemd` and Supervisor.

If you are not sure which one to choose, start with `systemd`.

- `systemd` is the default init system on most modern Linux distributions and usually requires no additional software.
- Choose Supervisor if you already use it in your infrastructure, or if `systemd` is not available in your environment.

## systemd

The recommended setup is a template service unit (`yii-queue@.service`) plus a target unit (`yii-queue.target`) that depends on a chosen number of worker instances.


### Template service unit: `yii-queue@.service`

```ini
# /etc/systemd/system/yii-queue@.service
[Unit]
Description=Yii Queue Worker %I
After=network.target
PartOf=yii-queue.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/app
ExecStart=/usr/bin/php /var/www/app/yii queue:listen
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

How it works:

- `%I` is the instance identifier. It becomes the `@<instance>` part in the unit name.
- `Restart=always` ensures a crashed worker is restarted.
- `PartOf=yii-queue.target` ties the worker lifecycle to the target. When you stop or restart the target, systemd stops/restarts the workers as well.

### Group target unit: `yii-queue.target`

```ini
# /etc/systemd/system/yii-queue.target
[Unit]
Description=Yii Queue Workers Group
After=network.target

# This target depends on 8 workers
Requires=yii-queue@1 yii-queue@2 yii-queue@3 yii-queue@4 yii-queue@5 yii-queue@6 yii-queue@7 yii-queue@8
Wants=yii-queue@1 yii-queue@2 yii-queue@3 yii-queue@4 yii-queue@5 yii-queue@6 yii-queue@7 yii-queue@8

[Install]
WantedBy=multi-user.target
```

How it works:

- `Requires` means the target requires these services to be started.
- `Wants` expresses a weaker relationship (it is still useful to keep it together with `Requires` for clarity).
- `[Install]` is required to make `systemctl enable yii-queue.target` work.

### Management commands

```bash
# Reload unit files after changes
sudo systemctl daemon-reload

# Start/stop/restart all workers
sudo systemctl start yii-queue.target
sudo systemctl stop yii-queue.target
sudo systemctl restart yii-queue.target

# Enable/disable autostart on boot for the whole group
sudo systemctl enable yii-queue.target
sudo systemctl disable yii-queue.target

# Enable and start all at once
sudo systemctl enable --now yii-queue.target

# Status
systemctl status yii-queue.target
systemctl status yii-queue@1

# Logs
journalctl -u "yii-queue@*" -f
journalctl -u yii-queue@1 -f
```

### Setup checklist

1. Place unit files in `/etc/systemd/system/`:

- `yii-queue@.service`
- `yii-queue.target`

2. Reload configuration:

```bash
sudo systemctl daemon-reload
```

3. Enable and start the worker group:

```bash
sudo systemctl enable --now yii-queue.target
```

### Changing worker count

To change the number of workers, update `Requires`/`Wants` in `yii-queue.target` and then reload and restart:

```bash
sudo systemctl daemon-reload
sudo systemctl restart yii-queue.target
```

## Supervisor

Supervisor manages multiple worker processes using a single configuration file and a control utility (`supervisorctl`).

### Configuration file

```ini
# /etc/supervisor/conf.d/yii-queue.conf
[program:yii-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/bin/php /var/www/app/yii queue:listen
autostart=true
autorestart=true
user=www-data
numprocs=8
redirect_stderr=true
stdout_logfile=/var/www/app/log/worker.log
```

How it works:

- `numprocs=8` starts 8 worker processes.
- `process_name` gives each process a unique name like `yii-queue-worker_00`, `yii-queue-worker_01`, etc.
- `autostart` starts workers when Supervisor starts.
- `autorestart` restarts a worker when it exits unexpectedly.

### Management commands

```bash
# Reload Supervisor configuration
sudo supervisorctl reread
sudo supervisorctl update

# Start/stop/restart all workers
sudo supervisorctl start yii-queue-worker:*
sudo supervisorctl stop yii-queue-worker:*
sudo supervisorctl restart yii-queue-worker:*

# Status
sudo supervisorctl status yii-queue-worker:*

# Logs (tail)
sudo supervisorctl tail -f yii-queue-worker:*
```

### Setup checklist

1. Place config file in `/etc/supervisor/conf.d/yii-queue.conf`.

2. Reload configuration:

```bash
sudo supervisorctl reread
sudo supervisorctl update
```

3. Start workers:

```bash
sudo supervisorctl start yii-queue-worker:*
```

## Cron

For simple workloads without a persistent process manager, you can run `queue:run` via cron. It processes all pending messages and exits.

```cron
* * * * * /usr/bin/php /var/www/app/yii queue:run
```

This starts a worker every minute. Use this only when the message volume is low and a small processing delay is acceptable. For high-throughput or latency-sensitive queues, use `systemd` or Supervisor with `queue:listen` instead.
