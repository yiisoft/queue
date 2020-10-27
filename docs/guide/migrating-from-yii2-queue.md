# Migrating from yii2-queue

This package is almost the same as [yiisoft/yii2-queue]. But some changes need to be explained. The new package is more about SOLID than the old one, and it can be used to send messages to consumers in another systems.

Drivers
-------
- Drivers are divided into separate packages. This means each driver must bre `requre`d with composer to be available in your application.
- Driver may be any class which implements `DriverInterface`. This means you can replace one driver with another without changing any code in your app. Use `db` driver in development while using `amqp` in production, then switch to `redis` if needed. This also means you can write your own driver implementation if necessary.

Jobs (Messages and Handlers)
----------------------------
There was a concept in [yiisoft/yii2-queue] called `Job`: you had to push it to the queue, and it was executed after being consumed. In the new package it is divided to two different concepts: a Message and a Handler.
- `Message` is a class which implements `MessageInterface`. It contains 3 types of data:
    - Name. This string is used by Worker to find the right handler for a message.
    - Data. Any serializable data which should be used by the message handler.
    - Behaviors. Message behaviors used by Drivers: priority setting, message delaying, etc. [See more][behaviours].
    
    All the message data is fully serializable (that means message `data` you're providing must be serializable too), so this packages doesn't restrict you to push and consume jobs in a single application. You can either use it to push messages and consume them anywhere else (e.g. in a golang worker), or just to consume messages pushed by some other system.
  
- `Handler`. Handlers are called by a `Worker` when a message comes. Default `Worker` finds message handler by the message name. [See more][handlers].

[yiisoft/yii2-queue]: (https://github.com/yiisoft/yii2-queue)
[behaviors]: (behaviors.md)
[behaviors]: (worker.md#handler-format)
