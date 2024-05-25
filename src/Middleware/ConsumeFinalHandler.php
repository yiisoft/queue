<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

use Closure;

/**
 * @internal
 */
final class ConsumeFinalHandler implements MessageHandlerInterface
{
    public function __construct(private Closure $handler)
    {
    }

    public function handle(Request $request): Request
    {
        $handler = $this->handler;
        $handler($request->getMessage());

        return $request;
    }
}
