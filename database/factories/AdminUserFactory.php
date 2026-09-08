<?php

namespace Database\Factories;

use App\Domain\Administration\AdminRole;
use App\Models\AdminUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class AdminUserFactory extends Factory
{
    protected $model = AdminUser::class;

    public function definition(): array
    {
        return ['name' => fake()->name(), 'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->unique()->numerify('##########'), 'password' => 'Seguro123',
            'role' => AdminRole::Admin, 'active' => true,
            'email_verified_at' => now(), 'phone_verified_at' => now()];
    }

    public function superadmin(): static
    {
        return $this->state(['role' => AdminRole::Superadmin, 'owner_slot' => 1]);
    }
}
