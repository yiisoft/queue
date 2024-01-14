<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

use Yiisoft\Queue\Exception\AdapterConfiguration\AdapterNotConfiguredException;

/**
 * @internal
 */
final class AdapterHandler implements MessageHandlerInterface
{
    public function handle(Request $request): Request
    {
        if (($adapter = $request->getAdapter()) === null) {
            throw new AdapterNotConfiguredException();
        }
        return $request->withMessage($adapter->push($request->getMessage()));
    }
}
