<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue;

use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;
use Yiisoft\Log\Logger;
use Yiisoft\Yii\Queue\Events\ExecEvent;

class JobProcessor implements JobProcessorInterface
{
    /**
     * @var EventDispatcherInterface
     */
    private EventDispatcherInterface $dispatcher;
    private Logger $logger;
    private LogMessageFormatter $formatter;

    public function __construct(
        EventDispatcherInterface $dispatcher,
        Logger $logger,
        LogMessageFormatter $formatter
    )
    {
        $this->dispatcher = $dispatcher;
        $this->logger = $logger;
        $this->formatter = $formatter;
    }

    /**
     * @param MessageInterface $message
     *
     * @param Queue $queue
     *
     * @throws Throwable
     */
    public function process(MessageInterface $message, Queue $queue): void
    {
        $event = ExecEvent::before($message->getId(), $message->getJob(), $message->getTtr(), $message->getAttempt());

        $title = $this->formatter->getExecTitle($event);
        $this->logger->info("$title is started.");

        try {
            $this->dispatcher->dispatch($event);
        } catch (Throwable $exception) {
            $this->handleError($queue, $event, $exception);

            throw $exception;
        }

        try {
            $event->result = $event->job->execute();
            $this->dispatcher->dispatch(ExecEvent::after($event));
        } catch (Throwable $exception) {
            $this->handleError($queue, $event, $exception);

            throw $exception;
        }

        $this->logger->info("$title is finished.");
    }

    /**
     * @param Queue $queue
     * @param ExecEvent $event
     *
     * @param Throwable $exception
     *
     * @return void
     */
    protected function handleError(Queue $queue, ExecEvent $event, Throwable $exception): void
    {
        $title = $this->formatter->getExecTitle($event);
        $this->logger->error("$title is finished with error: $exception.");
        $this->dispatcher->dispatch(ExecEvent::error($queue, $event, $exception));
    }
}
