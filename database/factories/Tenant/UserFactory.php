<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\User;
use App\Models\Tenant\Department;
use App\Models\Tenant\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\User>
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_code' => fake()->unique()->regexify('EMP[0-9]{6}'),
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => 'password', // Will be hashed by model mutator
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => fake()->phoneNumber(),
            'dept_id' => Department::factory(),
            'role_id' => Role::factory(),
            'is_active' => true,
            'last_login_at' => null,
            'password_changed_at' => now(),
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the user is an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::factory()->admin(),
        ]);
    }

    /**
     * Indicate that the user is a manager.
     */
    public function manager(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::factory()->manager(),
        ]);
    }

    /**
     * Indicate that the user is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the user has logged in recently.
     */
    public function recentlyLoggedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_login_at' => now()->subHours(fake()->numberBetween(1, 24)),
        ]);
    }

    /**
     * Indicate that the user belongs to a specific department.
     */
    public function inDepartment(int $deptId): static
    {
        return $this->state(fn (array $attributes) => [
            'dept_id' => $deptId,
        ]);
    }

    /**
     * Indicate that the user has a specific role.
     */
    public function withRole(int $roleId): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => $roleId,
        ]);
    }

    /**
     * Indicate that the user has a specific password.
     */
    public function withPassword(string $password): static
    {
        return $this->state(fn (array $attributes) => [
            'password_hash' => $password,
        ]);
    }
}
