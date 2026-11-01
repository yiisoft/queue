<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Cli;

/**
 * A loop that changes process-wide state, such as signal handlers, only while messages are consumed.
 *
 * {@see ScopedLoopInterface::start()} is called before consuming starts and {@see ScopedLoopInterface::finish()} after it ends. Calls may be nested, for example
 * when `queue:listen-all` runs consumers that share the same loop: the state is changed by the outermost
 * {@see ScopedLoopInterface::start()} and restored by the matching {@see ScopedLoopInterface::finish()}.
 */
interface ScopedLoopInterface extends LoopInterface
{
    /**
     * Prepares the loop before consuming messages.
     */
    public function start(): void;

    /**
     * Restores what {@see ScopedLoopInterface::start()} changed after consuming messages.
     */
    public function finish(): void;
}
