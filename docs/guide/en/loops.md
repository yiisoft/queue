# Loops

Yii Queue uses `\Yiisoft\Queue\Cli\LoopInterface` to control long-running execution.

The loop is evaluated to determine whether it can continue:

- Before each message in `QueueConsumer::run()` and `QueueConsumer::listen()`. A message taken from the queue after the
  loop stopped is not processed and goes back to the queue.
- On each iteration of `queue:listen-all`.

When `canContinue()` returns `false`, consuming stops gracefully (as soon as the current message is finished).

See also:

- [Console commands](console-commands.md)
- [Workers](worker.md)

## Built-in implementations

### `SignalLoop`

`\Yiisoft\Queue\Cli\SignalLoop` is used by default when `ext-pcntl` is available.

It supports:

- Graceful shutdown on `SIGHUP`, `SIGINT`, `SIGTERM`.
- Pause/resume via `SIGTSTP` and `SIGCONT`.
- Optional soft memory limit (see below).

To stop a worker gracefully, send `SIGTERM` to its process, for example:

```shell
kill -TERM <worker-pid>
```

The worker finishes the current message and leaves the remaining messages in the queue for the next worker.
Use `SIGTSTP` to pause processing and `SIGCONT` to resume it.

You can create or resolve `SignalLoop` in another console command without changing that command's signal handling.
Existing application handlers are restored when queue processing ends, including when processing fails.
While a queue worker is running, its graceful-shutdown behavior takes precedence over application handlers.

### `SimpleLoop`

`\Yiisoft\Queue\Cli\SimpleLoop` is used by default when `ext-pcntl` is **not** available.

It supports:

- Optional soft memory limit.

## Soft memory limit

Both built-in loops accept `memorySoftLimit` (in bytes):

- `0` means “no limit”.
- When the current process memory usage reaches the limit, `canContinue()` returns `false`.

This is useful for recycling long-running workers in process managers such as systemd or Supervisor.

## Configuration

### With `yiisoft/config`

By default, `LoopInterface` is resolved to `SignalLoop` when `ext-pcntl` is available, otherwise to `SimpleLoop`.

To set a soft memory limit, configure both loop implementations:

```php
use Yiisoft\Queue\Cli\SignalLoop;
use Yiisoft\Queue\Cli\SimpleLoop;

return [
    SignalLoop::class => [
        '__construct()' => [
            'memorySoftLimit' => 256 * 1024 * 1024,
        ],
    ],
    SimpleLoop::class => [
        '__construct()' => [
            'memorySoftLimit' => 256 * 1024 * 1024,
        ],
    ],
];
```

To force a specific implementation regardless of `ext-pcntl` availability, override `LoopInterface` binding:

```php
use Yiisoft\Queue\Cli\LoopInterface;
use Yiisoft\Queue\Cli\SimpleLoop;

return [
    LoopInterface::class => SimpleLoop::class,
];
```

### Manual configuration (without `yiisoft/config`)

Instantiate the loop you want and pass it to `QueueConsumer` (and, depending on adapter, to the adapter constructor as well):

```php
use Yiisoft\Queue\Cli\SignalLoop;

$loop = new SignalLoop(memorySoftLimit: 256 * 1024 * 1024);
```

When creating `RunCommand` or `ListenAllCommand` manually, pass the same loop instance to the command and its consumers.
This keeps graceful shutdown active between queues. For example, with your configured consumer provider:

```php
use Yiisoft\Queue\Command\RunCommand;

$command = new RunCommand($consumerProvider, $loop);
```

For your own execution loop, use `start()` and `finish()` to restore application signal handlers when work ends:

```php
$loop->start();
try {
    while ($loop->canContinue()) {
        // Process one unit of work.
    }
} finally {
    $loop->finish();
}
```

If you call `canContinue()` without `start()`, the loop keeps its signal handlers for the rest of the process.
Use the paired calls above when other application code needs to handle signals after your work ends.

## Writing a custom loop

Implement `LoopInterface` and encapsulate your own stopping conditions:

- Time limits.
- Message count limits.
- External stop flags.
- Integration with your own signal / shutdown handling.

The only requirement is that `canContinue()` returns `false` when the worker should stop.

Implement `ScopedLoopInterface` when your custom loop needs setup and cleanup around message processing.
Put setup in `start()` and cleanup in `finish()`; consumers and commands call these methods for you,
even if processing throws. Support nested calls when consumers share a loop with a command: keep the loop active
until the matching outermost `finish()`.

