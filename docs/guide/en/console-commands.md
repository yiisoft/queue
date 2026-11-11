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

`queue:consume` can report activity to terminals supporting the
[Program Status Protocol (OSC 7501)](https://www.superlogical.com/rex/docs/build/program-status).
Normal console output stays unchanged. No extra dependency is required.

Reports use the terminal attached to standard error when Symfony provides a separate error output.
For another stream output, they use that stream. Reporting requires positive TTY detection, enabled
ANSI decoration and non-quiet output on the selected stream. Redirecting standard error, running under
cron, disabling decoration or using quiet mode suppresses the reports. Forcing decoration does not
bypass TTY detection. Unknown or buffered outputs do not receive reports.

Set `YII_TERMINAL_STATUS=off` to disable reporting (`0`, `no` and `false` also disable it,
case-insensitively). Other values do not force reporting on redirected streams. OSC 7501 is sent
without querying the terminal. Delivery through SSH or multiplexers depends on their support;
an interactive SSH terminal can qualify, while a connection without a TTY cannot.

Each invocation uses a distinct record subtree with stable `app=queue`, so concurrent consumers and
Composer do not compete for the terminal's root record. Queue records use positions in the selected
queue list rather than names, supporting numeric, Unicode and otherwise unusual queue names without
identifier collisions. Repeated passes update those same records rather than allocate one per job.

A finite `--once` pass reports completion after processing and loop cleanup. Continuous polling remains
working while waiting for messages. When a persistent consumer returns, or the default signal loop is
interrupted, the invocation's subtree is replaced by an idle record rather than a completion notice.
The listening call may return immediately for a synchronous consumer; reports do not claim that it
opened a subscription. Custom consumer or loop implementations must provide their own cancellation
semantics; the command cannot infer why a custom finite consumer returned early.

Consumption failures report error for the active queue and the invocation. Messages contain queue
names and counts, not job bodies, credentials or exception details. Invalid UTF-8 messages are omitted;
valid messages are kept within the protocol's byte limit with control characters replaced. A failed
status stream disables further reports without changing the consumption result.

Only the command reports status. Adapters such as `yiisoft/queue-db`, `yiisoft/queue-redis` and
`yiisoft/queue-amqp` do not emit duplicate reports. The encoder, writer and Symfony adapter are internal
implementation details; applications can continue constructing `ConsumeCommand` with its provider and loop.
