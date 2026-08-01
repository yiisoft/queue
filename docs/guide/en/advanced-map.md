# Advanced documentation map

Use this index when you need to customize internals: custom middleware, adapters, queue providers, tooling, or diagnostics.

## Configuration and infrastructure

- [Manual configuration without yiisoft/config](configuration-manual.md) — wiring queues, workers, and middleware factories without `yiisoft/config`.
- [Queue provider registry](#queue-provider-registry) — selecting and extending adapter factories.
- [Loops and worker processes](loops.md) — implementing custom runners, heartbeat hooks, and graceful shutdown (requires `pcntl`).
- [Worker](worker.md) — resolving worker dependencies and starting workers.
- [Performance tuning](performance-tuning.md) — profiling handlers, envelopes, and adapters.

## Middleware, envelopes, and handlers

- [Middleware pipelines deep dive](middleware-pipelines.md) — dispatcher lifecycle, request mutations, and per-pipeline contracts.
- [Callable definitions and middleware factories](callable-definitions-extended.md) — container-aware definitions for middleware factories.
- [Error handling internals](error-handling-advanced.md) — failure pipeline flow, built-in components, and custom middleware.
- [Envelope metadata and stack reconstruction](envelopes-metadata-internals.md) — stack resolution and metadata merging.
- [Handler resolver pipeline](message-handler-advanced.md) — alternative handler lookup strategies.

## Queue adapters and interoperability

- [Custom queue provider implementations](queue-names-advanced.md#extending-the-registry) — bespoke selection logic, tenant registries, and fallback strategies.
- [Consuming messages from external systems](consuming-messages-from-external-systems.md) — contract for third-party producers.

## Tooling and diagnostics

- [Yii Debug collector internals](debug-integration-advanced.md) — collector internals, proxies, and manual wiring.

## Internals and contribution

- [Internals guide](../../internals.md) — local QA tooling (PHPUnit, Infection, Psalm, Rector, ComposerRequireChecker).

## Queue provider registry

When multiple queue names share infrastructure, use typed providers and a strict `queues[name][producer|consumer]` role map:

- `QueueProducerProviderInterface::getProducer($queueName)` resolves a configured `QueueProducerInterface`; `QueueConsumerProviderInterface::getConsumer($queueName)` resolves a `QueueConsumerInterface`.
- `QueueFactoryProvider` lazily creates each role from [`yiisoft/factory`](https://github.com/yiisoft/factory) definitions; `PredefinedQueueProvider` accepts ready role instances in the same map shape.
- `CompositeQueueProvider` aggregates typed providers and selects the first provider that has the requested role for the name.
- Implement the producer provider, consumer provider, or both to introduce custom registries or fallback strategies, then register the typed dependency in DI.
