<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Exception\AdapterConfiguration;

use RuntimeException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\QueueFactory;

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
        $factoryClass = QueueFactory::class;

        return <<<SOLUTION
            Adapter property must be set in the Queue object before you can use it.
            Please use either a constructor "\$adapter" parameter, or "withAdapter()" queue method
            to set an appropriate adapter.
            A more convenient way to get a configured Queue is a QueueFactory usage.

            References:
            - $queueClass::\$adapter
            - $queueClass::withAdapter()
            - $factoryClass::get()
            SOLUTION;
    }
}
