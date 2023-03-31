<?php

namespace TimoKoerber\LaravelOneTimeOperations\Commands\Utils;

use Illuminate\Console\View\Components\Factory;
use Illuminate\Support\Carbon;
use TimoKoerber\LaravelOneTimeOperations\Commands\OneTimeOperationsCommand;

class OperationsLineElement
{
    use ColoredOutput;

    public function __construct(
        public string $name,
        public string $status,
        public ?Carbon $processedAt = null,
        public ?string $tag = null,
    ) {
    }

    public static function make(string $name, string $status, Carbon $processedAt = null, string $tag = null): self
    {
        return new self($name, $status, $processedAt, $tag);
    }

    public function output(Factory $components): void
    {
        $components->twoColumnDetail($this->firstColumn(), $this->secondColumn());
    }

    protected function firstColumn(): string
    {
        $label = $this->name;

        if ($this->tag) {
            $label .= ' '.$this->gray('('.$this->tag.')');
        }

        return $label;
    }

    protected function secondColumn(): string
    {
        $label = $this->coloredStatus($this->status);

        if ($this->processedAt) {
            $label = $this->gray($this->processedAt).' '.$label;
        }

        return $label;
    }

    protected function coloredStatus(string $status): string
    {
        return match ($status) {
            OneTimeOperationsCommand::LABEL_DISPOSED => $this->green($status),
            OneTimeOperationsCommand::LABEL_PROCESSED => $this->brightgreen($status),
            default => $this->white($status),
        };
    }

    public function getStatus(): string
    {
        return $this->status;
    }
}
