# Advanced documentation map

Use this index when you need to customize internals: custom middleware, adapters, queue providers, tooling, or diagnostics.

## Configuration and infrastructure

- [Manual configuration without yiisoft/config](configuration-manual.md) — wiring queues, workers, and middleware factories without `yiisoft/config`.
- [Queue provider registry](#queue-provider-registry) — selecting and extending adapter factories.
- [Loops and worker processes](loops.md) — implementing custom runners, heartbeat hooks, and graceful shutdown (requires `pcntl`).
- [Worker internals](worker.md) — dependency resolution and middleware stacks within `WorkerInterface`.
- [Performance tuning](performance-tuning.md) — profiling handlers, envelopes, and adapters.

## Middleware, envelopes, and handlers

- [Middleware pipelines deep dive](middleware-pipelines.md) — dispatcher lifecycle, request mutations, and per-pipeline contracts.
- [Callable definitions and middleware factories](callable-definitions-extended.md) — container-aware definitions for middleware factories.
- [Error handling](error-handling.md#failure-pipeline-overview) — end-to-end flow of the failure pipeline.
- [Custom failure middleware](error-handling.md#how-to-create-a-custom-failure-middleware) — implementing `MiddlewareFailureInterface`.
- [Envelope metadata and stack reconstruction](envelopes.md#metadata-and-envelope-stacking) — stack resolution and metadata merging.
- [FailureEnvelope usage](error-handling.md#failureenvelope) — retry metadata semantics.
- [Handler resolver pipeline](message-handler.md#resolver-pipeline) — alternative handler lookup strategies.

## Queue adapters and interoperability

- [Custom queue provider implementations](queue-names-advanced.md#extending-the-registry) — bespoke selection logic, tenant registries, and fallback strategies.
- [Consuming messages from external systems](consuming-messages-from-external-systems.md) — contract for third-party producers.
- [Adapter internals](adapter-list.md#available-adapters) — extend or swap backend adapters.

## Tooling, diagnostics, and storage

- [Yii Debug collector internals](debug-integration-advanced.md) — collector internals, proxies, and manual wiring.
- [Job status storage extensions](job-status.md#extend-storage) — persisting custom metadata or drivers.
- [Extending queue processes and supervisors](process-managers.md#custom-supervisors) — custom supervisor hooks and graceful shutdown integration.

## Internals and contribution

- [Internals guide](../../internals.md) — local QA tooling (PHPUnit, Infection, Psalm, Rector, ComposerRequireChecker).

## Queue provider registry

When multiple queue names share infrastructure, rely on `QueueProviderInterface`:

- A queue name is passed to `QueueProviderInterface::get($queueName)` and resolved into a configured `QueueInterface` instance.
- Providers typically construct adapters lazily via [`yiisoft/factory`](https://github.com/yiisoft/factory) and call `AdapterInterface::withChannel($channel)` to switch broker-specific channels.
- Default implementation (`AdapterFactoryQueueProvider`) enforces a strict registry defined in `yiisoft/queue.channels`. Unknown names throw `ChannelNotFoundException`.
- Alternative providers include:
  - `PrototypeQueueProvider` — clones a base queue/adapter, switching only the channel name (useful when all queues share infrastructure but risks typos).
  - `CompositeQueueProvider` — aggregates multiple providers and selects the first that knows the queue name.
- Implement `QueueProviderInterface` to introduce custom registries or fallback strategies, then register the implementation in DI.
