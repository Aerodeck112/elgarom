<?php

namespace App\Calc;

/** One calculator: which workbook it runs and which cells are its inputs, parameters and results. */
final class ModelDefinition
{
    public function __construct(private readonly array $d) {}

    public function __get(string $name): mixed
    {
        return $this->d[$name] ?? null;
    }

    public function key(): string
    {
        return $this->d['key'];
    }

    public function sheets(): array
    {
        return $this->d['sheets'];
    }

    public function role(string $sheet): ?string
    {
        return $this->d['sheets'][$sheet]['role'] ?? null;
    }

    public function variants(): array
    {
        return $this->d['variants'];
    }

    public function isHours(): bool
    {
        return $this->d['kind'] === 'hours';
    }

    public function toArray(): array
    {
        return $this->d;
    }
}
