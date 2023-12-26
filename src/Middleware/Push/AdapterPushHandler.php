<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Push;

use Yiisoft\Queue\Exception\AdapterConfiguration\AdapterNotConfiguredException;

/**
 * @internal
 */
final class AdapterPushHandler implements MessageHandlerPushInterface
{
    public function handlePush(PushRequest $request): PushRequest
    {
        if ($request->getAdapter() === null) {
            throw new AdapterNotConfiguredException();
        }
        $request->getAdapter()->push($request->getMessage());

        return $request;
    }
}
