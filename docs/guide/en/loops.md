# Loops

Yii Queue uses `\Yiisoft\Queue\Cli\LoopInterface` to control long-running execution.

The loop is checked:

- After each processed message (via `Queue::run()` / `Queue::listen()`).
- On each iteration of `queue:listen-all`.

When the loop says it **cannot continue**, consuming stops gracefully (as soon as the current message is finished).

See also:

- [Console commands](console-commands.md)
- [Workers](worker.md)

## Loop interface

The interface is minimal:

```php
namespace Yiisoft\Queue\Cli;

interface LoopInterface
{
    public function canContinue(): bool;
}
```

Adapters receive a callback that returns `bool`. When the callback returns `false`, the adapter should stop consuming.

## Built-in implementations

### `SignalLoop`

`\Yiisoft\Queue\Cli\SignalLoop` is used by default when `ext-pcntl` is available.

It supports:

- Graceful shutdown on `SIGHUP`, `SIGINT`, `SIGTERM`.
- Pause/resume via `SIGTSTP` and `SIGCONT`.
- Optional soft memory limit (see below).

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

Instantiate the loop you want and pass it to `Queue` (and, depending on adapter, to adapter constructor as well):

```php
use Yiisoft\Queue\Cli\SignalLoop;

$loop = new SignalLoop(memorySoftLimit: 256 * 1024 * 1024);
```

## Writing a custom loop

Implement `LoopInterface` and encapsulate your own stopping conditions:

- Time limits.
- Message count limits.
- External stop flags.
- Integration with your own signal / shutdown handling.

The only requirement is that `canContinue()` returns `false` when the worker should stop.

