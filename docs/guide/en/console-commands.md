# Console commands

Yii Queue provides several console commands for processing queued messages.

If you are using [yiisoft/config](https://github.com/yiisoft/config) and [yiisoft/yii-console](https://github.com/yiisoft/yii-console), the commands are registered automatically.

If you are using [symfony/console](https://github.com/symfony/console) directly, you should register the commands manually.

> **Note:** The default queue name list (used when no queue names are passed to a command) is only available when using [yiisoft/config](https://github.com/yiisoft/config) and [yiisoft/yii-console](https://github.com/yiisoft/yii-console). Without them, you must pass the queue name list explicitly to the command constructor.

In [yiisoft/app](https://github.com/yiisoft/app) the `yii` console binary is provided out of the box.
If you are using [yiisoft/yii-console](https://github.com/yiisoft/yii-console) or `symfony/console` without that template, invoke these commands the same way you invoke other console commands in your application.

## 1. Run queued messages and exit

The command `queue:run` obtains and handles messages until the queue is empty, then exits.  

You can also narrow the scope of processed messages by specifying queue name(s) and maximum number of messages to process:

- Specify one or more queue names to process. Messages from other queues will be ignored. Defaults to all registered queue names.
- Use `--limit` to limit the number of messages processed. When set, command will exit either when all the messages are processed or when the maximum count is reached.

The full command signature is:
```sh
yii queue:run [queueName1 [queueName2 [...]]] --limit=100
```

## 2. Listen for queued messages and process them continuously

The following command launches a daemon, which infinitely consumes messages from a single queue. This command receives an optional `queueName` argument to specify which queue to listen to, defaults to the queue name `yii-queue`.

```sh
yii queue:listen [queueName]
```

> **Note:** If the queue is not configured with an adapter (synchronous mode), the command logs an info message and exits gracefully.

## 3. Listen to multiple queues

The following command iterates through multiple queues and is meant to be used in development environment only, as it consumes a lot of CPU for iterating through queues. You can pass to it:

- `queueName` argument(s). Specify one or more queue names to process. Messages from other queues will be ignored. Defaults to all registered queue names.
- `--limit` option to limit the number of messages processed before switching to another queue. E.g. you set `--limit` to 500 and right now you have 1000 messages in `queue1`. This command will consume only 500 of them, then it will switch to `queue2` to see if there are any messages there. Defaults to `0` (no limit).
- `--pause` option to specify the number of seconds to pause between checking queues when no messages are found. Defaults to `1`.

The full command signature is:
```sh
yii queue:listen-all [queueName1 [queueName2 [...]]] --pause=1 --limit=0
```

For long-running processes, graceful shutdown is controlled by `LoopInterface`. When `ext-pcntl` is available,
the default `SignalLoop` handles signals such as `SIGTERM`/`SIGINT`.
