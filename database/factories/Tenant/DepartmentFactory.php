<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Department::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dept_code' => fake()->unique()->regexify('[A-Z]{3}[0-9]{3}'),
            'dept_name' => fake()->words(2, true) . ' Department',
            'parent_dept_id' => null,
            'is_active' => true,
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the department is a root department.
     */
    public function root(): static
    {
        return $this->state(fn (array $attributes) => [
            'dept_code' => 'ROOT',
            'dept_name' => 'Root Department',
            'parent_dept_id' => null,
        ]);
    }

    /**
     * Indicate that the department has a parent.
     */
    public function withParent(?int $parentId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'parent_dept_id' => $parentId ?? Department::factory(),
        ]);
    }

    /**
     * Indicate that the department is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
