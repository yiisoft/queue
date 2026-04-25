<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Adapter\AdapterInterface;
use Yiisoft\Queue\Message\MessageInterface;

/**
 * @internal
 */
final class AdapterPushHandler implements MessageHandlerPushInterface
{
    public function __construct(
        private readonly AdapterInterface $adapter,
    ) {}

    public function handlePush(MessageInterface $message): MessageInterface
    {
        return $this->adapter->push($message);
    }
}
