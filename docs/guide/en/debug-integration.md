# Yii Debug integration

This package provides optional integration with [yiisoft/yii-debug](https://github.com/yiisoft/yii-debug).

When enabled, optional native push and worker middleware add queue events to the queue collector. Push events contain the normalized queue key and the message from the final push handler's returned `PushRequest`; this is not necessarily a message returned by an adapter (for example, synchronous pushing can process and replace the message). Worker events are recorded before handler resolution. A push event is recorded only when the downstream push handler returns; an exception prevents that event, and middleware that short-circuits before the debug middleware is reached bypasses it. Status instrumentation is separate and wraps only the `QueueProducerStatusProviderInterface` capability.

The instrumentation is optional: applications can enable or omit each middleware and the status provider wrapper independently. There are no producer, consumer, or worker debug proxy/decorator services.

For manual wiring and tracked events, see [Advanced Yii Debug integration](debug-integration-advanced.md).
