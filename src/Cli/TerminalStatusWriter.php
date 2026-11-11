<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Cli;

use Throwable;

use function fflush;
use function fwrite;
use function is_resource;
use function strlen;

/** @internal Writes reports within one invocation's record subtree. */
final class TerminalStatusWriter
{
    /** @param resource|null $stream An explicitly selected output stream; it is never closed by this writer. */
    public function __construct(
        private $stream,
        private bool $enabled,
        private readonly string $id,
        private readonly string $app = 'queue',
    ) {
        Osc7501Encoder::encode('clear', $id, $app);
        $this->enabled = $enabled && is_resource($stream);
    }

    public function emit(
        string $state,
        ?string $message = null,
        ?string $id = null,
        ?int $progress = null,
        ?string $kind = null,
    ): void {
        if (!$this->isEnabled()) {
            return;
        }

        $this->write(Osc7501Encoder::encode(
            $state,
            $id === null ? $this->id : $this->id . '/' . $id,
            $this->app,
            $message,
            $progress,
            $kind,
        ));
    }

    /** Clears only this invocation's subtree, or a child beneath it. Never clears the terminal root. */
    public function clear(?string $id = null): void
    {
        $this->emit('clear', id: $id);
    }

    public function isEnabled(): bool
    {
        return $this->enabled && is_resource($this->stream);
    }

    private function write(string $sequence): void
    {
        $stream = $this->stream;
        if (!is_resource($stream)) {
            return;
        }

        try {
            // Terminal status is optional. A closed or failed sink must not change the work's outcome.
            if (@fwrite($stream, $sequence) !== strlen($sequence) || !@fflush($stream)) {
                $this->enabled = false;
            }
        } catch (Throwable) {
            $this->enabled = false;
        }
    }
}
