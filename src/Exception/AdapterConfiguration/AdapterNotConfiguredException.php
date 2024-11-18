<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Exception\AdapterConfiguration;

use RuntimeException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Queue\Provider\QueueProviderInterface;
use Yiisoft\Queue\Queue;

class AdapterNotConfiguredException extends RuntimeException implements FriendlyExceptionInterface
{
    protected $message = 'Queue adapter is not configured';

    public function getName(): string
    {
        return 'Adapter is not configured';
    }

    public function getSolution(): ?string
    {
        $queueClass = Queue::class;
        $queueProviderInterface = QueueProviderInterface::class;

        return <<<SOLUTION
            Adapter property must be set in the Queue object before you can use it.
            Please use either a constructor "\$adapter" parameter, or "withAdapter()" queue method
            to set an appropriate adapter.
            A more convenient way to get a configured Queue is a QueueFactory usage.

            References:
            - $queueClass::\$adapter
            - $queueClass::withAdapter()
            - $queueProviderInterface
            SOLUTION;
    }
}
