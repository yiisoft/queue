<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

use Throwable;

/**
 * @internal Internal package class, please don't use it directly
 */
final class FailureFinalHandler implements MessageHandlerInterface
{
    public function __construct(private Throwable $exception)
    {
    }

    /**
     * @throws Throwable
     */
    public function handle(Request $request): Request
    {
        // TODO: Add tests
        //throw $request->getException() ?? new \RuntimeException('Message processing failed.');
        throw $this->exception;
    }
}
