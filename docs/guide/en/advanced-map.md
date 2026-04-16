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

When multiple queue names share infrastructure, rely on `QueueProviderInterface`:

- A queue name is passed to `QueueProviderInterface::get($queueName)` and resolved into a configured `QueueInterface` instance.
- Default implementation (`AdapterFactoryQueueProvider`) enforces a strict registry defined in `yiisoft/queue.queues`. Unknown names throw `QueueNotFoundException`.
- Alternative providers include:
  - `PredefinedQueueProvider` — accepts a pre-built map of queue name → `QueueInterface` instance.
  - `QueueFactoryProvider` — creates queue objects lazily from [`yiisoft/factory`](https://github.com/yiisoft/factory) definitions.
  - `CompositeQueueProvider` — aggregates multiple providers and selects the first that knows the queue name.
- Implement `QueueProviderInterface` to introduce custom registries or fallback strategies, then register the implementation in DI.
