# Message behaviors

`Behaviors` are classes designed to tell queue [drivers] about some specific message behavior: e.g. when you want to delay message consuming or set message priority. There are some predefined behaviors with which some default [drivers] can work.

***Important!* Not every driver can work with all the behaviors. There will be a matrix below showing compatible drivers and behaviors.**

## Behavior purposes
### DelayBehavior
This behavior specifies amount of seconds the message can be consumed after.

This code means the message can be consumed after 5 seconds since it was pushed to the queue server: `$message->attachBehavior(new DelayBehavior(5))`.

### PriorityBehavior
This behavior sets a relative order for message consuming. The higher the priority, the earlier the message will be consumed.

Example: `$message->attachBehavior(new PriorityBehavior(100))`

## Compatibility matrix
|                     | DelayBehavior             | PriorityBehavior |
| -                   | -                         | -                |
| [SynchronousDriver] | ❌                         | ❌                |
| [Amqp]              | yiisoft/yii-queue-amqp#11 | ❌                |

[drivers]: (driver-list.md)
[SynchronousDriver]: (driver-sync.md)
[Amqp]: (https://github.com/yiisoft/yii-queue-amqp)
