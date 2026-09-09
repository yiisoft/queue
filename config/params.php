<?php

declare(strict_types=1);

use Yiisoft\Queue\Command\ListenAllCommand;
use Yiisoft\Queue\Command\ListenCommand;
use Yiisoft\Queue\Command\RunCommand;
use Yiisoft\Queue\Debug\QueueCollector;
use Yiisoft\Queue\Debug\QueueProducerStatusProviderProxy;
use Yiisoft\Queue\Message\Handler\HandlerInterface;
use Yiisoft\Queue\Message\Serializer\MessageSerializer;
use Yiisoft\Queue\Provider\QueueProducerStatusProviderInterface;

return [
    'yiisoft/yii-console' => [
        'commands' => [
            'queue:run' => RunCommand::class,
            'queue:listen' => ListenCommand::class,
            'queue:listen-all' => ListenAllCommand::class,
        ],
    ],
    'yiisoft/queue' => [
        /**
         * Map of message type to message class. Used by {@see MessageSerializer} to reconstruct the original typed
         * message object on unserialize. Example:
         * [
         *     'send-email' => SendEmailMessage::class,
         *     'generate-report' => GenerateReportMessage::class,
         * ]
         */
        'messages' => [],
        /**
         * Map of message type to handler. The worker uses this to find the handler for a received message.
         * A handler may be a class name implementing {@see HandlerInterface}, a callable, or any definition
         * supported by yiisoft/injector. Example:
         * [
         *     'send-email' => SendEmailHandler::class,
         *     'generate-report' => [GenerateReportHandler::class, 'handle'],
         * ]
         */
        'handlers' => [],
        'middlewares-push' => [],
        'middlewares-worker' => [],
        'middlewares-consume' => [],
        'middlewares-fail' => [],
    ],
    'yiisoft/yii-debug' => [
        'collectors' => [
            QueueCollector::class,
        ],
        'trackedServices' => [
            QueueProducerStatusProviderInterface::class => [QueueProducerStatusProviderProxy::class, QueueCollector::class],
        ],
    ],
];
