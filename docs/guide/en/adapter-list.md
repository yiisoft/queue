Queue Adapters
-------------

An adapter connects the queue to a message broker or storage backend. Without an adapter the queue runs in [synchronous mode](no-adapter.md) — suitable for development and tests, **not for production**.

## Official adapters

* [AMQP](https://github.com/yiisoft/queue-amqp) — adapter over AMQP protocol via [amqplib](https://github.com/php-amqplib/php-amqplib)

## Community adapters

* [NATS](https://github.com/g41797/queue-nats) — [NATS](https://nats.io/) JetStream adapter
* [Pulsar](https://github.com/g41797/queue-pulsar) — [Apache Pulsar](https://pulsar.apache.org/) adapter
* [SQS](https://github.com/g41797/queue-sqs) — [Amazon SQS](https://aws.amazon.com/sqs/) adapter
* [Kafka](https://github.com/g41797/queue-kafka) — [Apache Kafka](https://kafka.apache.org/) adapter
* [Valkey](https://github.com/g41797/queue-valkey) — [Valkey NoSQL data store](https://valkey.io/) adapter
* [Beanstalkd](https://github.com/g41797/queue-beanstalkd) — [Beanstalkd — simple, fast work queue](https://beanstalkd.github.io/) adapter
