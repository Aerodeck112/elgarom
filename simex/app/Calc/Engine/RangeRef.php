<?php

namespace App\Calc\Engine;

/** A reference to a rectangular block of cells on one sheet (1-based, inclusive). */
final class RangeRef
{
    public function __construct(
        public readonly int $sheet,
        public readonly int $r1,
        public readonly int $c1,
        public readonly int $r2,
        public readonly int $c2,
    ) {}

    public function rows(): int
    {
        return $this->r2 - $this->r1 + 1;
    }

    public function cols(): int
    {
        return $this->c2 - $this->c1 + 1;
    }

    public function isCell(): bool
    {
        return $this->r1 === $this->r2 && $this->c1 === $this->c2;
    }
}
