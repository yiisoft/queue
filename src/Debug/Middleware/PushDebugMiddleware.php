<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug\Middleware;

use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Middleware\Push\PushHandlerInterface;
use Yiisoft\Queue\Middleware\Push\PushMiddlewareInterface;
use Yiisoft\Queue\Middleware\Push\PushRequest;

use function in_array;
use function is_string;

use const DEBUG_BACKTRACE_IGNORE_ARGS;

final class PushDebugMiddleware implements PushMiddlewareInterface
{
    public function __construct(private readonly QueueCollector $collector) {}

    public function processPush(PushRequest $request, PushHandlerInterface $handler): PushRequest
    {
        $source = $this->sourceFrame();
        $result = $handler->handlePush($request);
        $this->collector->collectPush(
            $request->getQueueName(),
            $result->getMessage(),
            $source['file'] . ':' . $source['line'],
        );
        return $result;
    }

    /**
     * Find the first frame outside the queue's push pipeline.
     *
     * The pipeline contains anonymous handlers, so its depth changes with the
     * configured middleware. Looking at the owning files/classes keeps the
     * external source stable when middleware is added or removed.
     *
     * @return array{file: string, line: int}
     */
    private function sourceFrame(): array
    {
        /** @var list<array{file?: string, line?: int, class?: string}> $trace */
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        foreach ($trace as $frame) {
            $file = $frame['file'] ?? null;
            if (!is_string($file) || !isset($frame['line']) || $this->isPipelineFrame($frame, $file)) {
                continue;
            }

            return ['file' => $file, 'line' => $frame['line']];
        }

        return ['file' => __FILE__, 'line' => __LINE__];
    }

    /**
     * @param array{file?: string, line?: int, class?: string} $frame
     */
    private function isPipelineFrame(array $frame, string $file): bool
    {
        $class = $frame['class'] ?? '';
        if (
            str_starts_with($class, 'Yiisoft\\Queue\\Middleware\\')
            || $class === 'Yiisoft\\Queue\\AsyncQueueProducer'
            || $class === 'Yiisoft\\Queue\\SyncQueueProducer'
        ) {
            return true;
        }

        return in_array(
            basename($file),
            [
                'PushDebugMiddleware.php',
                'PushMiddlewareDispatcher.php',
                'PushMiddlewareStack.php',
                'AsyncQueueProducer.php',
                'SyncQueueProducer.php',
            ],
            true,
        );
    }
}
