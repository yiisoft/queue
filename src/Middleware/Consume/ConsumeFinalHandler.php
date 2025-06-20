<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

use Closure;

/**
 * @internal
 */
final class ConsumeFinalHandler implements MessageHandlerConsumeInterface
{
    public function __construct(
        private readonly Closure $handler
    ) {
    }

    public function handleConsume(ConsumeRequest $request): ConsumeRequest
    {
        $handler = $this->handler;
        $handler($request->getMessage());

        return $request;
    }
}
