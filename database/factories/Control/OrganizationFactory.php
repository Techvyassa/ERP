<?php

namespace Database\Factories\Control;

use App\Models\Control\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Control\Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Organization::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);
        
        return [
            'org_slug' => $slug,
            'org_name' => fake()->company(),
            'tenant_db_name' => "erp_{$slug}",
            'registration_status' => 'ACTIVE',
            'primary_email' => fake()->unique()->companyEmail(),
            'primary_phone' => fake()->phoneNumber(),
            'address_line1' => fake()->streetAddress(),
            'address_line2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => fake()->countryCode(),
            'timezone' => fake()->timezone(),
            'currency_code' => fake()->currencyCode(),
            'max_users' => fake()->numberBetween(5, 100),
            'created_at' => now(),
        ];
    }

    /**
     * Indicate that the organization is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_status' => 'PENDING',
            'activated_at' => null,
        ]);
    }

    /**
     * Indicate that the organization is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_status' => 'ACTIVE',
            'activated_at' => now()->subDays(fake()->numberBetween(1, 365)),
        ]);
    }

    /**
     * Indicate that the organization is suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_status' => 'SUSPENDED',
            'suspended_at' => now()->subDays(fake()->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate that the organization is terminated.
     */
    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'registration_status' => 'TERMINATED',
            'terminated_at' => now()->subDays(fake()->numberBetween(1, 90)),
        ]);
    }
}
