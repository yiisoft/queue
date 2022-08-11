<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware;

use InvalidArgumentException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

final class InvalidCallableConfigurationException extends InvalidArgumentException implements FriendlyExceptionInterface
{
    public function getName(): string
    {
        return 'Invalid event listener configuration.';
    }

    public function getSolution(): ?string
    {
        return <<<SOLUTION
            The callable has incorrect configuration. To meet the requirements a callable should be one of:
            - A closure.
            - [object, method] array.
            - [class name, method] array.
            - [DI container service ID, method] array.
            - DI container service ID string which references a class with the `__invoke()` method

            If you are using a classname or an alias string to be passed to a DI container please check if it is properly configured in the DI container.
        SOLUTION;
    }
}
