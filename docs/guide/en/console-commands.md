# Console commands

Yii Queue provides several console commands for processing queued jobs.

If you are using [yiisoft/config](https://github.com/yiisoft/config) and [yiisoft/yii-console](https://github.com/yiisoft/yii-console), the commands are registered automatically.

If you are using [symfony/console](https://github.com/symfony/console) directly, you should register the commands manually.

In [yiisoft/app](https://github.com/yiisoft/app) the `yii` console binary is provided out of the box.
If you are using [yiisoft/console](https://github.com/yiisoft/console) or `symfony/console` without that template, invoke these commands the same way you invoke other console commands in your application.

## 1. Run queued messages and exit

The command `queue:run` obtains and executes tasks until the queue is empty, then exits.  

You can also narrow the scope of processed messages by specifying channel(s) and maximum number of messages to process:

- Specify one or more channels to process. Messages from other channels will be ignored. Default is all registered channels (in case of using [yiisoft/config](https://github.com/yiisoft/config) and [yiisoft/yii-console](https://github.com/yiisoft/yii-console), otherwise pass the default channel list to the command constructor).
- Use `--maximum` to limit the number of messages processed. When set, command will exit either when all the messages are processed or when the maximum count is reached. Despite the name, it acts as a limit.

The full command signature is:
```sh
yii queue:run [channel1 [channel2 [...]]] --maximum=100
```

## 2. Listen for queued messages and process them continuously

The following command launches a daemon, which infinitely consumes messages from a single channel of the queue. This command receives an optional `channel` argument to specify which channel to listen to, defaults to the default channel `yii-queue`.

```sh
yii queue:listen [channel]
```

## 3. Listen to multiple channels

The following command iterates through multiple channels and is meant to be used in development environment only, as it consumes a lot of CPU for iterating through channels. You can pass to it:

- `channel` argument(s). Specify one or more channels to process. Messages from other channels will be ignored. Default is all registered channels (in case of using [yiisoft/config](https://github.com/yiisoft/config) and [yiisoft/yii-console](https://github.com/yiisoft/yii-console), otherwise pass the default channel list to the command constructor).
- `--maximum` option to limit the number of messages processed before switching to another channel. E.g. you set `--maximum` to 500 and right now you have 1000 messages in `channel1`. This command will consume only 500 of them, then it will switch to `channel2` to see if there are any messages there. Defaults to `0` (no limit).
- `--pause` option to specify the number of seconds to pause between checking channels when no messages are found. Defaults to `1`.

`queue:listen` does not have a `--maximum` option. If you need to stop after processing a certain number of messages, use `queue:run --maximum=...`.

The full command signature is:
```sh
yii queue:listen-all [channel1 [channel2 [...]]] --pause=1 --maximum=0
```

> The command alias `queue:listen:all` exists for backward compatibility and may be removed in a future release, since it was a typo.

For long-running processes, graceful shutdown is controlled by `LoopInterface`. When `ext-pcntl` is available,
the default `SignalLoop` handles signals such as `SIGTERM`/`SIGINT`.
