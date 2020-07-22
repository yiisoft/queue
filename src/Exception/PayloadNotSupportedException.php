<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception;

use Throwable;
use UnexpectedValueException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Yii\Queue\Driver\DriverInterface;
use Yiisoft\Yii\Queue\Payload\DelayablePayloadInterface;
use Yiisoft\Yii\Queue\Payload\PayloadInterface;
use Yiisoft\Yii\Queue\Payload\PrioritisedPayloadInterface;
use Yiisoft\Yii\Queue\Payload\RetryablePayloadInterface;

class PayloadNotSupportedException extends UnexpectedValueException implements FriendlyExceptionInterface
{
    private DriverInterface $driver;
    private PayloadInterface $payload;

    public function __construct(
        DriverInterface $driver,
        PayloadInterface $payload,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        if ($message === '') {
            $driverClass = get_class($driver);
            $payloadName = $payload->getName();
            $message = "$driverClass does not support payload \"$payloadName\".";
        }

        parent::__construct($message, $code, $previous);

        $this->driver = $driver;
        $this->payload = $payload;
    }

    public function getName(): string
    {
        return 'Payload is not supported by current queue driver';
    }

    public function getSolution(): ?string
    {
        $defaultInterfaces = [
            DelayablePayloadInterface::class,
            PrioritisedPayloadInterface::class,
            RetryablePayloadInterface::class,
        ];
        $interfaces = array_intersect($defaultInterfaces, class_implements($this->payload));
        $interfaces = implode(', ', $interfaces);

        $payloadName = $this->payload->getName();
        $driverClass = get_class($this->driver);

        return <<<SOLUTION
            The given payload $payloadName implements next system interfaces:
            $interfaces.

            Here is a list of all default interfaces which can be unsupported by different queue drivers:
            - DelayablePayloadInterface (allows to execute job with a delay)
            - PrioritisedPayloadInterface (is used to prioritize job execution)
            - RetryablePayloadInterface (allows to execute the job multiple times while it fails)

            The given driver $driverClass does not support one of them, or even more.
            The solution is in one of these:
            - Check which interfaces does $driverClass support and remove not supported interfaces from $payloadName.
            - Use another driver which supports all interfaces you need. Officially supported drivers are:
                - None yet :) Work is in progress.
            SOLUTION;
    }
}
