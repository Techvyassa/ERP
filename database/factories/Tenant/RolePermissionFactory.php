<?php

namespace Database\Factories\Tenant;

use App\Models\Tenant\RolePermission;
use App\Models\Tenant\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant\RolePermission>
 */
class RolePermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = RolePermission::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $modules = ['PR', 'PO', 'GRN', 'QC', 'INVOICE', 'PAYMENT', 'INVENTORY', 'REPORTS'];
        
        return [
            'role_id' => Role::factory(),
            'module_code' => fake()->randomElement($modules),
            'can_view' => fake()->boolean(80),
            'can_create' => fake()->boolean(60),
            'can_edit' => fake()->boolean(50),
            'can_approve' => fake()->boolean(30),
            'can_delete' => fake()->boolean(20),
            'created_by' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the permission has full access.
     */
    public function fullAccess(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_view' => true,
            'can_create' => true,
            'can_edit' => true,
            'can_approve' => true,
            'can_delete' => true,
        ]);
    }

    /**
     * Indicate that the permission is read-only.
     */
    public function readOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_view' => true,
            'can_create' => false,
            'can_edit' => false,
            'can_approve' => false,
            'can_delete' => false,
        ]);
    }

    /**
     * Indicate that the permission has no access.
     */
    public function noAccess(): static
    {
        return $this->state(fn (array $attributes) => [
            'can_view' => false,
            'can_create' => false,
            'can_edit' => false,
            'can_approve' => false,
            'can_delete' => false,
        ]);
    }

    /**
     * Indicate that the permission is for a specific module.
     */
    public function forModule(string $moduleCode): static
    {
        return $this->state(fn (array $attributes) => [
            'module_code' => $moduleCode,
        ]);
    }

    /**
     * Indicate that the permission allows viewing.
     */
    public function canView(bool $value = true): static
    {
        return $this->state(fn (array $attributes) => [
            'can_view' => $value,
        ]);
    }

    /**
     * Indicate that the permission allows creating.
     */
    public function canCreate(bool $value = true): static
    {
        return $this->state(fn (array $attributes) => [
            'can_create' => $value,
        ]);
    }

    /**
     * Indicate that the permission allows editing.
     */
    public function canEdit(bool $value = true): static
    {
        return $this->state(fn (array $attributes) => [
            'can_edit' => $value,
        ]);
    }

    /**
     * Indicate that the permission allows approving.
     */
    public function canApprove(bool $value = true): static
    {
        return $this->state(fn (array $attributes) => [
            'can_approve' => $value,
        ]);
    }

    /**
     * Indicate that the permission allows deleting.
     */
    public function canDelete(bool $value = true): static
    {
        return $this->state(fn (array $attributes) => [
            'can_delete' => $value,
        ]);
    }
}
