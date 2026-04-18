<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

/**
 * @internal
 */
final class AdapterPushHandler implements MessageHandlerPushInterface
{
    public function handlePush(PushRequest $request): PushRequest
    {
        return $request->withMessage(
            $request->getAdapter()->push(
                $request->getMessage(),
            ),
        );
    }
}
