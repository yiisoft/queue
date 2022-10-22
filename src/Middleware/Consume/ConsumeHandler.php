<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Consume;

use Closure;

/**
 * @internal
 */
final class ConsumeHandler implements MessageHandlerConsumeInterface
{
    public function __construct(private Closure $handler)
    {
    }

    public function handleConsume(ConsumeRequest $request): ConsumeRequest
    {
        $handler = $this->handler;
        $handler($request->getMessage());

        return $request;
    }
}
