<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // The migrations supply initial community and category catalogs.
        Role::findOrCreate('client', 'web');
        Role::findOrCreate('provider', 'web');
    }
}
