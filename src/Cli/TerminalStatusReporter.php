<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Cli;

use Exception;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

use function bin2hex;
use function getenv;
use function in_array;
use function is_resource;
use function random_bytes;
use function stream_isatty;
use function strtolower;
use function trim;

/** @internal Selects a positively verified terminal output for queue status reports. */
final class TerminalStatusReporter
{
    public static function forOutput(OutputInterface $output): TerminalStatusWriter
    {
        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }

        $stream = $output instanceof StreamOutput ? $output->getStream() : null;
        $setting = getenv('YII_TERMINAL_STATUS');
        $enabled = !in_array(strtolower(trim($setting === false ? '' : $setting)), ['off', '0', 'no', 'false'], true)
            && $output->isDecorated()
            && !$output->isQuiet()
            && is_resource($stream)
            && stream_isatty($stream);

        $id = 'queue/disabled';
        if ($enabled) {
            try {
                $id = 'queue/' . bin2hex(random_bytes(8));
            } catch (Exception) {
                $enabled = false;
            }
        }

        return new TerminalStatusWriter($stream, $enabled, $id);
    }
}
