<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Message;

use Yiisoft\Yii\Queue\Message\Behaviors\BehaviorInterface;

interface MessageInterface
{
    public function setId(?string $id): void;

    /**
     * Returns unique message id
     *
     * @return string
     */
    public function getId(): ?string;

    /**
     * Returns payload name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns payload data
     *
     * @return mixed
     */
    public function getData();

    /**
     * Attaches behavior to the message
     *
     * @param BehaviorInterface $behavior
     *
     * @return MessageInterface
     */
    public function attachBehavior(BehaviorInterface $behavior): self;

    /**
     * Returns attached behaviors
     *
     * @return BehaviorInterface[]
     */
    public function getBehaviors(): array;

    /**
     * Returns attached behavior by its name
     *
     * @param string $behaviorClassName
     *
     * @return BehaviorInterface|null
     */
    public function getBehavior(string $behaviorClassName): ?BehaviorInterface;
}
