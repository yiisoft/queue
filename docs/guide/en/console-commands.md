# Console commands

Yii Queue provides the `queue:consume` console command for processing queued messages.

If you are using [yiisoft/config](https://github.com/yiisoft/config) and [yiisoft/yii-console](https://github.com/yiisoft/yii-console), the command is registered automatically.

If you are using [symfony/console](https://github.com/symfony/console) directly, you should register `Yiisoft\Queue\Command\ConsumeCommand` manually, passing a consumer provider and the same loop instance used by its consumers.

> **Note:** `queue:consume --once` and `queue:consume --all` use `QueueConsumerProviderInterface::getConsumerQueueNames()` when no queue names are passed. Explicitly passed names are resolved with `getConsumer()` and must have a consumer role.

In [yiisoft/app](https://github.com/yiisoft/app) the `yii` console binary is provided out of the box.
If you are using [yiisoft/yii-console](https://github.com/yiisoft/yii-console) or `symfony/console` without that template, invoke these commands the same way you invoke other console commands in your application.

## 1. Run queued messages and exit

`queue:consume --once` handles existing messages until the queues are empty, then exits.
`--once` means one pass through the selected queues, not one message.

You can also narrow the scope of processed messages by specifying queue name(s) and maximum number of messages to process:

- Specify one or more queue names to process. Messages from other queues will be ignored. Defaults to all registered consumer-capable queue names.
- Use `--limit` to limit the number of messages processed. When set, command will exit either when all the messages are processed or when the maximum count is reached.

The full command signature is:
```sh
yii queue:consume --once [queueName1 [queueName2 [...]]] --limit=100
```

## 2. Listen for queued messages and process them continuously

The following command launches a daemon, which infinitely consumes messages from a single queue. This command receives an optional `queueName` argument to specify which queue to listen to, defaults to the queue name `yii-queue`.

```sh
yii queue:consume [queueName]
```

> **Note:** If the queue is not configured with an adapter (synchronous mode), the command logs an info message and exits gracefully.

The `--limit` and `--pause` options are not accepted in this single-queue listening mode.
Use `--once --limit=100` to process up to 100 existing messages per queue and exit.

## 3. Listen to multiple queues

The following command iterates through multiple queues and is meant to be used in development environment only, as it consumes a lot of CPU for iterating through queues. You can pass to it:

- `queueName` argument(s). Specify one or more queue names to process. Messages from other queues will be ignored. Defaults to all registered consumer-capable queue names.
- `--limit` option to limit the number of messages processed before switching to another queue. E.g. you set `--limit` to 500 and right now you have 1000 messages in `queue1`. This command will consume only 500 of them, then it will switch to `queue2` to see if there are any messages there. Defaults to `0` (no limit).
- `--pause` option to specify the number of seconds to pause between checking queues when no messages are found. Defaults to `1`.

Use `--all --once` to process those queues for one pass and exit. Passing several queue names requires `--all` or `--once`.
`--once` takes precedence over `--all`. The `--pause` option is accepted only with `--all` without `--once`.

The full command signature is:
```sh
yii queue:consume --all [queueName1 [queueName2 [...]]] --pause=1 --limit=0
```

For long-running processes, graceful shutdown is controlled by `LoopInterface`. When `ext-pcntl` is available,
the default `SignalLoop` handles signals such as `SIGTERM`/`SIGINT`.

## Terminal program status (OSC 7501)

When run in an interactive terminal, `queue:consume` reports what it is doing — processing or waiting on messages —
and how it finished, using the [Program Status Protocol (OSC 7501)](https://www.superlogical.com/rex/docs/build/program-status),
so the terminal itself can show the status in its UI.

The reports are written directly to the output stream. They require no extra dependencies and appear only when the
stream is an interactive TTY and the output is decorated and not quiet; redirected output, log files, cron runs and
quiet invocations are unaffected. Set the environment variable `YII_TERMINAL_STATUS` to `off` (also `0`, `no`,
`false`) to disable the reports explicitly; `YII_QUEUE_STATUS` is accepted as a compatibility alias.

In synchronous mode (no adapter configured), `queue:consume` without flags does not listen, so it reports no
"listening" status: it finishes with a `done` report saying that messages are processed on push.

Only the command itself reports status; adapters such as `yiisoft/queue-db`, `yiisoft/queue-redis` or
`yiisoft/queue-amqp` do not emit their own terminal status.
