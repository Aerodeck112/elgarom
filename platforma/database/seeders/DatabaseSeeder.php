<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Initial content only. No user accounts are seeded: the first administrator
     * is created by install.php with a password chosen during installation.
     */
    public function run(): void
    {
        $this->call(ContentSeeder::class);
    }
}
