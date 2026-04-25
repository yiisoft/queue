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
        $adapter = $request->getAdapter();

        if ($adapter === null) {
            return $request;
        }

        return $request->withMessage(
            $adapter->push($request->getMessage()),
        );
    }
}
