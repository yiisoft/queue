# Producing messages from external systems

This guide explains how to publish messages to a queue backend (RabbitMQ, Kafka, SQS, etc.) from *external producers* (including non-PHP services) so that `yiisoft/queue` consumers can correctly deserialize and process these messages.

The key idea is simple:

- The queue adapter reads a *raw payload* (usually a string) from the broker.
- The adapter passes that payload to a `Yiisoft\Queue\Message\MessageSerializerInterface` implementation.
- By default, `yiisoft/queue` config binds `MessageSerializerInterface` to `Yiisoft\Queue\Message\JsonMessageSerializer`.

`JsonMessageSerializer` is only the default implementation. You can replace it with your own serializer by rebinding `Yiisoft\Queue\Message\MessageSerializerInterface` in your DI configuration.

So, external systems should produce the **same payload format** that your consumer-side serializer expects (JSON described below is for the default `JsonMessageSerializer`).

## 1. Handler name contract (most important part)

`yiisoft/queue` resolves a handler by message handler name (`MessageInterface::getHandlerName()`).

For external producers, you should not rely on PHP FQCN handler names. Prefer a stable short name and map it in the consumer application configuration (see [Message handler](message-handler.md)).

Example mapping:

```php
return [
    'yiisoft/queue' => [
        'handlers' => [
            'file-download' => \App\Queue\RemoteFileHandler::class,
        ],
    ],
];
```

External producer then always publishes `"name": "file-download"`.

## 2. JSON payload format (JsonMessageSerializer)

`Yiisoft\Queue\Message\JsonMessageSerializer` expects the message body to be a JSON object with these keys:

- `name` (string, required)
- `data` (any JSON value, optional; defaults to `null`)
- `meta` (object, optional; defaults to `{}`)

Minimal example:

```json
{
  "name": "file-download",
  "data": {
    "url": "https://example.com/file.pdf",
    "destinationFile": "/tmp/file.pdf"
  }
}
```

Full example:

```json
{
  "name": "file-download",
  "data": {
    "url": "https://example.com/file.pdf",
    "destinationFile": "/tmp/file.pdf"
  },
  "meta": {
    "trace-id": "1f2c0e10b7b44c67",
    "tenant-id": "acme"
  }
}
```

### Notes about `meta`

The `meta` key is a general-purpose metadata container (for example, tracing, correlation, tenant information). External systems may populate it, and the consumer-side application or middleware may also read, add, or override keys as needed. However, it's not recommended, as it highly depends on the consumer-side application code.

## 3. Data encoding rules

- The payload must be UTF-8 JSON.
- `data` and `meta` must contain only JSON-encodable values:
  - strings, numbers, booleans, null
  - arrays
  - objects (maps)

If your broker stores bytes, publish the UTF-8 bytes of the JSON string.

## 4. Publishing to a broker: what exactly to send

`yiisoft/queue` itself does not define a network protocol. The exact “where” this JSON goes depends on the adapter:

- Some adapters put this JSON into the broker message **body**.
- Some adapters may additionally use broker headers/attributes.

For external producers you should:

- Use the adapter documentation of your chosen backend (AMQP / Kafka / SQS / etc.) to know which queue/topic and routing settings to use.
- Ensure the **message body** is exactly the JSON described above (unless the adapter docs explicitly say otherwise).

## 5. Examples (non-PHP)

These examples show how to produce the JSON body. You still need to publish it with your broker-specific client.

### Python (constructing JSON body)

```python
import json

payload = {
    "name": "file-download",
    "data": {"url": "https://example.com/file.pdf"}
}

body = json.dumps(payload, ensure_ascii=False).encode("utf-8")
```

### Node.js (constructing JSON body)

```js
const payload = {
  name: 'file-download',
  data: { url: 'https://example.com/file.pdf' },
};

const body = Buffer.from(JSON.stringify(payload), 'utf8');
```

### curl (for HTTP-based brokers / gateways)

```sh
curl -X POST \
  -H 'Content-Type: application/json' \
  --data '{"name":"file-download","data":{"url":"https://example.com/file.pdf"}}' \
  https://your-broker-gateway.example.com/publish
```
