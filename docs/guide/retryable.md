Errors and retryable jobs
=========================

The execution of a job can fail. This can be due to internal errors which result from
poorly written code which should be fixed first. But they can also fail due to external
problems like a service or a resource being unavailable. This can lead to exceptions or
timeouts.

AttemptsRestrictedPayloadInterface
---------------------

```php
class RetryablePayload extends SimplePayload implements AttemptsRestrictedPayloadInterface
{
    public function getAttempts(): int
    {
        return 2;
    }

    public function getName(): string
    {
        return 'retryable';
    }
}
```

Restrictions
------------

Full support for retryable payload is implemented in the [AMQP Interop](https://github.com/yiisoft/yii-queue-amqp) driver.
The [Sync](driver-sync.md) driver will not retry failed jobs
