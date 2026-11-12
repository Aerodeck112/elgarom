<?php

namespace App\Calc\Engine;

/** An Excel error value (#N/A, #VALUE!, ...). One shared instance per code. */
final class ExcelError
{
    /** @var array<string, self> */
    private static array $pool = [];

    private function __construct(public readonly string $code) {}

    public static function of(string $code): self
    {
        return self::$pool[$code] ??= new self($code);
    }

    public static function na(): self
    {
        return self::of('#N/A');
    }

    public static function value(): self
    {
        return self::of('#VALUE!');
    }

    public static function div0(): self
    {
        return self::of('#DIV/0!');
    }

    public static function ref(): self
    {
        return self::of('#REF!');
    }

    public static function name(): self
    {
        return self::of('#NAME?');
    }

    public static function num(): self
    {
        return self::of('#NUM!');
    }

    public function __toString(): string
    {
        return $this->code;
    }
}
