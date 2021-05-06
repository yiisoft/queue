<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception\AdapterConfiguration;

use InvalidArgumentException;
use Throwable;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

class ChannelNotConfiguredException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    public function __construct(string $channelName, int $code = 0, Throwable $previous = null)
    {
        $message = "Queue channel '$channelName' is not properly configured.";
        parent::__construct($message, $code, $previous);
    }

    public function getName(): string
    {
        return 'Queue channel is not properly configured';
    }

    public function getSolution(): ?string
    {
        // TODO: Implement getSolution() method.
    }
}
