# Message behaviors

`Behaviors` are classes designed to tell queue [adapters] about some specific message behavior: e.g. when you want
to delay message consuming or set message priority. There are some predefined behaviors with which some
default [adapters] can work.

***Important!* Not every adapter can work with all the behaviors. Below you will find a matrix displaying compatibility
of adapters and behaviors.**

## Default behaviors

### DelayBehavior

This behavior specifies amount of seconds the message can be consumed after.

This code means the message can be consumed after 5 seconds since it was pushed to the queue server:

```php
$message->attachBehavior(new DelayBehavior(5));
```

### PriorityBehavior

This behavior sets a relative order for message consuming. The higher the priority, the earlier a message will be consumed.

Example:

```php
$message->attachBehavior(new PriorityBehavior(100));
```

## Compatibility matrix

|                     | DelayBehavior             | PriorityBehavior
|---------------------|---------------------------|-----------------
| [SynchronousAdapter] | ❌                        | ❌                
| [Amqp]              | yiisoft/yii-queue-amqp#11 | ❌               


[adapters]: (adapter-list.md)
[SynchronousAdapter]: (adapter-sync.md)
[Amqp]: (https://github.com/yiisoft/yii-queue-amqp)
