<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Push;

use Yiisoft\Yii\Queue\Exception\AdapterConfiguration\AdapterNotConfiguredException;

/**
 * @internal
 */
final class AdapterPushHandler implements MessageHandlerPushInterface
{
    public function handlePush(PushRequest $request): PushRequest
    {
        if (($adapter = $request->getAdapter()) === null) {
            throw new AdapterNotConfiguredException();
        }
        return $request->withMessage($adapter->push($request->getMessage()));
    }
}
