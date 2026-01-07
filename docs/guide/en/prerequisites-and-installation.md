# Prerequisites and installation

## Requirements

- PHP 8.1 or higher.
- PCNTL extension for signal handling (optional, recommended for production use).

If `ext-pcntl` is not installed, workers cannot handle OS signals (such as `SIGTERM`/`SIGINT`) gracefully.
In practice it means a process manager may terminate a worker at any time, which can interrupt a job in the middle of execution.
See [Loops](loops.md) for details.

## Installation

Install the package with [Composer](https://getcomposer.org):

```shell
composer require yiisoft/queue
```

## Next steps

- [Configuration with yiisoft/config](configuration-with-config.md)
- [Manual configuration](configuration-manual.md)
- [Adapter list](adapter-list.md)
