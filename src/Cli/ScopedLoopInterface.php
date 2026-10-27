<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Cli;

/**
 * A loop that changes process-wide state, such as signal handlers, only while messages are consumed.
 *
 * {@see start()} is called before consuming starts and {@see finish()} after it ends. Calls may be nested, for example
 * when `queue:listen-all` runs consumers that share the same loop: the state is changed by the outermost
 * {@see start()} and restored by the matching {@see finish()}.
 */
interface ScopedLoopInterface extends LoopInterface
{
    /**
     * Prepares the loop before consuming messages.
     */
    public function start(): void;

    /**
     * Restores what {@see start()} changed after consuming messages.
     */
    public function finish(): void;
}
