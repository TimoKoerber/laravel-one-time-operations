<?php

namespace TimoKoerber\LaravelOneTimeOperations;

abstract class OneTimeOperation
{
    /**
     * Determine if the operation is being processed asyncronously.
     */
    protected bool $async = true;

    /**
     * The queue that the job will be dispatched to.
     */
    protected string $queue = 'default';

    /**
     * A tag name, that this operation can be filtered by.
     */
    protected ?string $tag = null;

    /**
     * Process the operation.
     */
    abstract public function process(): void;

    public function isAsync(): bool
    {
        return $this->async;
    }

    public function getQueue(): string
    {
        return $this->queue;
    }

    public function getTag(): ?string
    {
        return $this->tag;
    }
}
