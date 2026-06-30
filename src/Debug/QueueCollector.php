<?php

declare(strict_types=1);

namespace Yiisoft\Queue\Debug;

use Yiisoft\Queue\MessageStatus;
use Yiisoft\Yii\Debug\Collector\CollectorTrait;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;
use Yiisoft\Queue\Message\MessageInterface;
use Yiisoft\Queue\QueueInterface;

use function count;

final class QueueCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /**
     * @var array[]
     */
    private array $pushes = [];
    private array $statuses = [];

    /**
     * @var array[]
     */
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

    public function collectStatus(string $id, MessageStatus $status, string $line): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->statuses[] = [
            'id' => $id,
            'status' => $status->key(),
            'line' => $line,
        ];
    }

    public function collectPush(string $queueName, MessageInterface $message, string $line): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->pushes[$queueName][] = [
            'message' => $message,
            'line' => $line,
        ];
    }

    public function collectWorkerProcessing(MessageInterface $message, QueueInterface $queue): void
    {
        if (!$this->isActive()) {
            return;
        }
        $this->processingMessages[$queue->getName()][] = $message;
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        $countPushes = array_sum(
            array_map(
                count(...),
                $this->pushes,
            ),
        );
        $countStatuses = count($this->statuses);
        $countProcessingMessages = array_sum(
            array_map(
                count(...),
                $this->processingMessages,
            ),
        );

        return [
            'countPushes' => $countPushes,
            'countStatuses' => $countStatuses,
            'countProcessingMessages' => $countProcessingMessages,
        ];
    }

    private function reset(): void
    {
        $this->pushes = [];
        $this->statuses = [];
        $this->processingMessages = [];
    }
}
