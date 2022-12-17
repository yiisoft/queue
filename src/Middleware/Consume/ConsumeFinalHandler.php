<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Middleware\Consume;

use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class ConsumeFinalHandler implements MessageHandlerConsumeInterface
{
    public function __construct(private string $handler, private LoggerInterface $logger)
    {
    }

    public function handleConsume(ConsumeRequest $request): ConsumeRequest
    {
        $class = $this->handler;
        $handler = new $class($this->logger);
        $handler->handle($request->getMessage());

        return $request;
    }
}
