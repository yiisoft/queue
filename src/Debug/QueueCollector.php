<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Debug;

use Yiisoft\Yii\Debug\Collector\CollectorTrait;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Message\MessageInterface;
use Yiisoft\Yii\Queue\Middleware\Push\MiddlewarePushInterface;
use Yiisoft\Yii\Queue\QueueInterface;

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

        $statusText = match (true) {
            $status->isDone() => 'done',
            $status->isReserved() => 'reserved',
            $status->isWaiting() => 'waiting',
            default => 'unknown'
        };
        $this->statuses[] = [
            'id' => $id,
            'status' => $statusText,
        ];
    }

    public function collectPush(
        string $channel,
        MessageInterface $message,
        string|array|callable|MiddlewarePushInterface ...$middlewareDefinitions,
    ): void {
        if (!$this->isActive()) {
            return;
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
        $this->processingMessages[$queue->getChannelName()][] = $message;
    }

    /**
     * @scrutinizer ignore-unused Called in yiisoft/yii-debug
     */
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
