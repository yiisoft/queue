<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception\AdapterConfiguration;

use InvalidArgumentException;
use Throwable;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Yii\Queue\QueueFactory;

class ChannelNotConfiguredException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    private string $channel;

    public function __construct(string $channel, int $code = 0, Throwable $previous = null)
    {
        $message = "Queue channel \"$channel\" is not properly configured.";
        $this->channel = $channel;
        parent::__construct($message, $code, $previous);
    }

    public function getName(): string
    {
        return 'Queue channel is not properly configured';
    }

    public function getSolution(): ?string
    {
        $factoryClass = QueueFactory::class;

        return <<<SOLUTION
            Channel "$this->channel" creation is not configured in the $factoryClass.
            Please take a look to the documentation for the $factoryClass constructor.
            The most important parameters are "\$definitions" and "\$enableRuntimeChannelDefinition".

            SOLUTION;
    }
}
