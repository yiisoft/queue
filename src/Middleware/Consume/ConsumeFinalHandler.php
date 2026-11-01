<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware\Consume;

use Closure;
use Yiisoft\Queue\Message\Envelope;

/**
 * @internal
 */
final class ConsumeFinalHandler implements ConsumeHandlerInterface
{
    public function __construct(
        private readonly Closure $handler,
    ) {}

    public function handleConsume(ConsumeRequest $request): ConsumeRequest
    {
        $message = $request->getMessage();

        // The handler is registered for a message type and is given that message, not whatever
        // the adapter wrapped it in on the way. The envelope's meta is carried over rather than
        // dropped with the wrapper: the envelope holds the merged meta while the message it wraps
        // keeps its own, so unwrapping alone would lose the id, the delay and the failure counts.
        // Middleware run before this step and still see the envelope itself.
        if ($message instanceof Envelope) {
            $message = $message->getMessage()->withMeta($message->getMeta());
        }

        $handler = $this->handler;
        $handler($message);

        return $request;
    }
}
