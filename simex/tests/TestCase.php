<?php

namespace Tests;

use App\Models\Company;
use App\Models\Entity;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    /** A signed-in user that has already passed the two-factor challenge. */
    protected function signIn(string $role = 'super_admin'): User
    {
        $user = new User(['name' => 'Test '.$role, 'email' => $role.uniqid().'@example.test', 'role' => $role, 'is_active' => true]);
        $user->forceFill(['password' => Hash::make('parola-de-test-123'), 'two_factor_secret' => 'JBSWY3DPEHPK3PXP', 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($user)->withSession(['admin.2fa_passed' => true, 'admin.last_seen' => time(), 'admin.confirmed_at' => time()]);

        return $user;
    }

    protected function company(array $attrs = []): Company
    {
        return Company::create($attrs + ['name' => 'Client Test SRL', 'stage' => 'lead', 'kind' => 'client', 'language' => 'ro', 'country' => 'RO']);
    }

    protected function issuer(bool $confirmed = true): Entity
    {
        return Entity::create(['code' => 'emitent-'.uniqid(), 'name' => 'Emitent Test', 'is_issuer' => true, 'quote_prefix' => 'OT'] + ($confirmed
            ? ['legal_name' => 'Emitent Test SRL', 'tax_id' => 'RO000', 'address' => 'Str. Test 1', 'legal_confirmed_at' => now()] : []));
    }

    /** Folder with the confidential source workbooks (not part of the repository). */
    protected function workbookDir(): string
    {
        return storage_path('app/private/calc-models/source');
    }
}
