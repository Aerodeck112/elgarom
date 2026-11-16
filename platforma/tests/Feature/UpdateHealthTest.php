<?php

namespace Tests\Feature;

use App\Services\Updates\Installer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpdateHealthTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function check(): array
    {
        $installer = app(Installer::class);
        $m = new \ReflectionMethod($installer, 'healthCheck');
        try {
            $m->invoke($installer, config('elgarom.version'));
            $error = null;
        } catch (\RuntimeException $e) {
            $error = $e->getMessage();
        } finally {
            \Illuminate\Support\Facades\Artisan::call('up');
        }
        $log = (new \ReflectionProperty($installer, 'log'))->getValue($installer);

        return [$error, implode("\n", $log)];
    }

    public function test_blocked_request_to_own_domain_falls_back_to_internal_check(): void
    {
        Http::fake(['*' => Http::response('Forbidden by firewall', 403)]);
        [$error, $log] = $this->check();
        $this->assertNull($error);
        $this->assertStringContainsString('HTTP 403', $log);
        $this->assertStringContainsString('Verificare internă: OK', $log);
    }

    public function test_server_error_after_update_still_triggers_restore(): void
    {
        Http::fake(['*' => Http::response('Your Composer dependencies require a PHP version ">= 8.3.0"', 500)]);
        [$error] = $this->check();
        $this->assertStringContainsString('HTTP 500', (string) $error);
        $this->assertStringContainsString('8.3.0', (string) $error);
    }

    public function test_healthy_site_passes_over_http(): void
    {
        Http::fake(['*' => Http::response(['ok' => true, 'version' => config('elgarom.version')])]);
        [$error, $log] = $this->check();
        $this->assertNull($error);
        $this->assertStringContainsString('Verificare HTTP /health: OK', $log);
    }
}
