<?php

namespace App\Calc;

/** The calculators known to the application (one definition file each). */
final class ModelRegistry
{
    /** @var array<string, ModelDefinition>|null */
    private ?array $cache = null;

    /** @return array<string, ModelDefinition> */
    public function all(): array
    {
        if ($this->cache === null) {
            $this->cache = [];
            foreach (glob(__DIR__.'/Definitions/*.php') ?: [] as $file) {
                $def = new ModelDefinition(require $file);
                $this->cache[$def->key()] = $def;
            }
            ksort($this->cache);
        }

        return $this->cache;
    }

    public function get(string $key): ?ModelDefinition
    {
        return $this->all()[$key] ?? null;
    }

    public function find(string $key): ModelDefinition
    {
        return $this->get($key) ?? abort(404);
    }
}
