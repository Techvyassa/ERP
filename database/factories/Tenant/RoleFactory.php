<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Role>
 */
class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Role::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_code' => fake()->unique()->regexify('[A-Z]{4}_[A-Z]{3}'),
            'role_name' => fake()->words(2, true) . ' Role',
            'description' => fake()->sentence(),
            'is_active' => true,
            'is_system_role' => false,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the role is an admin role.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_code' => 'ADMIN',
            'role_name' => 'Administrator',
            'description' => 'Full system access with all permissions',
            'is_system_role' => true,
        ]);
    }

    /**
     * Indicate that the role is a manager role.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_code' => 'MANAGER',
            'role_name' => 'Manager',
            'description' => 'Department manager with approval permissions',
            'is_system_role' => true,
        ]);
    }

    /**
     * Indicate that the role is a user role.
     */
    public function user(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_code' => 'USER',
            'role_name' => 'User',
            'description' => 'Standard user with basic permissions',
            'is_system_role' => true,
        ]);
    }

    /**
     * Indicate that the role is a viewer role.
     */
    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_code' => 'VIEWER',
            'role_name' => 'Viewer',
            'description' => 'Read-only access to system',
            'is_system_role' => true,
        ]);
    }

    /**
     * Indicate that the role is a system role.
     */
    public function systemRole(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_system_role' => true,
        ]);
    }

    /**
     * Indicate that the role is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
