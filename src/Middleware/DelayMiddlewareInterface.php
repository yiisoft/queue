<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Middleware;

/**
 * A middleware interface for message delaying. It must be implemented in an adapter package or in a project.
 */
interface DelayMiddlewareInterface extends MiddlewareInterface
{
    /**
     * Set a new delay value into the middleware object
     *
     * @param float $seconds Delay value in seconds
     *
     * @return $this A new middleware object with changed delay value
     */
    public function withDelay(float $seconds): self;
}
