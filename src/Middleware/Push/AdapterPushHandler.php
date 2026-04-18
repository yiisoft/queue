<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Adapter\AdapterInterface;

/**
 * @internal
 */
final class AdapterPushHandler implements MessageHandlerPushInterface
{
    public function __construct(
        private readonly AdapterInterface $adapter,
    ) {}

    public function handlePush(PushRequest $request): PushRequest
    {
        return $request->withMessage(
            $this->adapter->push(
                $request->getMessage(),
            ),
        );
    }
}
