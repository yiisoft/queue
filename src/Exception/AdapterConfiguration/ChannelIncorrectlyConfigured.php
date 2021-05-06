<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception\AdapterConfiguration;

use InvalidArgumentException;
use Throwable;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Yii\Queue\Adapter\AdapterInterface;

class ChannelIncorrectlyConfigured extends InvalidArgumentException implements FriendlyExceptionInterface
{
    /**
     * ChannelIncorrectlyConfigured constructor.
     *
     * @param string $channel
     * @param mixed|object $object
     */
    public function __construct(string $channel, $object, int $code = 0, ?Throwable $previous = null)
    {
        $adapterClass = AdapterInterface::class;
        $realType = is_object($object) ? get_class($object) : gettype($object);
        $message = "Channel '$channel' is not properly configured: definition must return $adapterClass, $realType returned";

        parent::__construct($message, $code, $previous);
    }

    public function getName(): string
    {
        return 'Incorrect queue channel configuration';
    }

    public function getSolution(): ?string
    {
        // TODO: Implement getSolution() method.
    }
}
