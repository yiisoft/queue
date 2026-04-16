# Worker

To use a worker, you should resolve the worker's dependencies (e.g., through DI container) and [define handlers](message-handler-advanced.md) for each message that will be consumed by a worker.

## Starting Workers

To start a worker, you should run the console commands such as `queue:run`, `queue:listen`, and `queue:listen-all`. See [Console commands](console-commands.md) for details.

For production-grade process management with `systemd`, Supervisor, or cron, see [Running workers in production](process-managers.md).
