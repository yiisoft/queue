<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use Yiisoft\Queue\JobStatus;
use Yiisoft\Yii\Debug\Collector\CollectorTrait;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Queue\QueueInterface;

final class QueueCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private array $pushes = [];
    private array $statuses = [];
    private array $processingMessages = [];

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'pushes' => $this->pushes,
            'statuses' => $this->statuses,
            'processingMessages' => $this->processingMessages,
        ];
    }

    public function collectStatus(string $id, JobStatus $status): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->statuses[] = [
            'id' => $id,
            'status' => $status->key(),
        ];
    }

    public function collectPush(
        ?string $channel,
        MessageInterface $message,
        string|array|callable|MiddlewarePushInterface ...$middlewareDefinitions,
    ): void {
        if (!$this->isActive()) {
            return;
        }
        if ($channel === null) {
            $channel = 'null';
        }

        $this->pushes[$channel][] = [
            'message' => $message,
            'middlewares' => $middlewareDefinitions,
        ];
    }

    public function collectWorkerProcessing(MessageInterface $message, QueueInterface $queue): void
    {
        if (!$this->isActive()) {
            return;
        }
        $this->processingMessages[$queue->getChannel()][] = $message;
    }

    private function reset(): void
    {
        $this->pushes = [];
        $this->statuses = [];
        $this->processingMessages = [];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        $countPushes = array_sum(array_map(fn ($messages) => is_countable($messages) ? count($messages) : 0, $this->pushes));
        $countStatuses = count($this->statuses);
        $countProcessingMessages = array_sum(array_map(fn ($messages) => is_countable($messages) ? count($messages) : 0, $this->processingMessages));

        return [
            'queue' => [
                'countPushes' => $countPushes,
                'countStatuses' => $countStatuses,
                'countProcessingMessages' => $countProcessingMessages,
            ],
        ];
    }
}
