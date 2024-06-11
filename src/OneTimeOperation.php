<?php

namespace TimoKoerber\LaravelOneTimeOperations;

abstract class OneTimeOperation
{
    /**
     * Determine if the operation is being processed asynchronously.
     */
    protected bool $async = true;

    /**
     * The Queue connection which the job will run into asynchronously
     */
    protected string $connection = 'database';

    /**
     * The queue that the job will be dispatched to.
     */
    protected string $queue = 'default';

    /**
     * The queue that the job will be delayed in seconds.
     */
    protected int $delay = 0;

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

    public function getConnection(): string
    {
        return $this->connection;
    }

    public function getDelay(): string
    {
        return $this->delay;
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
