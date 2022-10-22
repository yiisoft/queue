# Migrating from yii2-queue

This package is similar to [yiisoft/yii2-queue] but with improved design and code style. The new package less coupled
and more structured than the old one allowing better maintenance.

## Adapters

- Individual adapters are now separate packages. This means each adapter must be `require`d with composer in order to
  be available in your application.
- Adapter may be any class which implements `AdapterInterface`. This means you can replace one adapter with another without
  changing any code in your app. For example, you can use `db` adapter in development while using `amqp` in production,
  then you may seamlessly switch to `redis` if needed. This also means you can write your own adapter implementation
  if necessary.

## Jobs (Messages and Handlers)

There was a concept in [yiisoft/yii2-queue] called `Job`: you had to push it to the queue, and it was executed after
being consumed. In the new package it is divided into two different concepts: a message and a handler.

- A `Message` is a class implementing `MessageInterface`. It contains 3 types of data:
    - Name. Worker uses it to find the right handler for a message.
    - Data. Any serializable data which should be used by the message handler.
    - Behaviors. Message behaviors used by adapters. For example, priority setting, message delaying, etc. [See more](behaviors.md).
    
    All the message data is fully serializable (that means message `data` must be serializable too). It allows you to
    freely choose where and how to send and process jobs. Both can be implemented in a single application, or
    separated into multiple applications, or you can do sending/processing only leaving part of the job to another
    system including non-PHP ones. It is fairly popular to process heavy jobs with Go.
  
- A `Handler` is called by a `Worker` when a message comes. Default `Worker` finds a corresponding message handler
  by the message name. [See more](worker.md#handler-format).

[yiisoft/yii2-queue]: (https://github.com/yiisoft/yii2-queue)
