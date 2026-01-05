<?php

namespace Kwaku\LaravelTestMcp\Services;

use Kwaku\LaravelTestMcp\DTOs\ClassAnalysis;

class FactoryGenerator
{
    public function generate(ClassAnalysis $analysis): string
    {
        if (!$analysis->isModel()) {
            throw new \InvalidArgumentException('Factory can only be generated for models');
        }

        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'namespace Database\Factories;';
        $lines[] = '';
        $lines[] = 'use Illuminate\Database\Eloquent\Factories\Factory;';
        $lines[] = "use {$analysis->className};";
        $lines[] = '';
        $lines[] = "/**";
        $lines[] = " * @extends Factory<{$analysis->shortName}>";
        $lines[] = " */";
        $lines[] = "class {$analysis->shortName}Factory extends Factory";
        $lines[] = '{';
        $lines[] = "    protected \$model = {$analysis->shortName}::class;";
        $lines[] = '';
        $lines[] = '    /**';
        $lines[] = '     * Define the model\'s default state.';
        $lines[] = '     *';
        $lines[] = '     * @return array<string, mixed>';
        $lines[] = '     */';
        $lines[] = '    public function definition(): array';
        $lines[] = '    {';
        $lines[] = '        return [';

        $fillable = $analysis->laravelFeatures['fillable'] ?? [];
        $casts = $analysis->laravelFeatures['casts'] ?? [];

        if (!empty($fillable)) {
            foreach ($fillable as $field) {
                $castType = $casts[$field] ?? null;
                $fakerValue = $this->generateFakerValue($field, $castType);
                $lines[] = "            '{$field}' => {$fakerValue},";
            }
        } else {
            $lines[] = '            // TODO: Add factory fields based on your model';
            $lines[] = "            // 'name' => fake()->name(),";
            $lines[] = "            // 'email' => fake()->unique()->safeEmail(),";
        }

        $lines[] = '        ];';
        $lines[] = '    }';

        // Add common state methods
        $lines[] = '';
        $lines[] = '    /**';
        $lines[] = '     * Indicate that the model is in a specific state.';
        $lines[] = '     */';
        $lines[] = '    public function customState(): static';
        $lines[] = '    {';
        $lines[] = '        return $this->state(fn (array $attributes) => [';
        $lines[] = '            // Define custom state attributes';
        $lines[] = '        ]);';
        $lines[] = '    }';

        $lines[] = '}';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function generateFakerValue(string $field, ?string $castType): string
    {
        // Normalize field name for matching
        $normalizedField = strtolower($field);

        // Check for common field patterns
        if ($normalizedField === 'email' || str_contains($normalizedField, '_email') || str_ends_with($normalizedField, 'email')) {
            return 'fake()->unique()->safeEmail()';
        }

        if ($normalizedField === 'name' || $normalizedField === 'full_name' || $normalizedField === 'fullname') {
            return 'fake()->name()';
        }

        if ($normalizedField === 'first_name' || $normalizedField === 'firstname') {
            return 'fake()->firstName()';
        }

        if ($normalizedField === 'last_name' || $normalizedField === 'lastname') {
            return 'fake()->lastName()';
        }

        if ($normalizedField === 'username' || $normalizedField === 'user_name') {
            return 'fake()->userName()';
        }

        if ($normalizedField === 'password') {
            return 'bcrypt(\'password\')';
        }

        if ($normalizedField === 'phone' || str_contains($normalizedField, 'phone') || str_contains($normalizedField, 'mobile')) {
            return 'fake()->phoneNumber()';
        }

        if ($normalizedField === 'address' || str_contains($normalizedField, 'address')) {
            return 'fake()->address()';
        }

        if ($normalizedField === 'city') {
            return 'fake()->city()';
        }

        if ($normalizedField === 'state' || $normalizedField === 'province') {
            return 'fake()->state()';
        }

        if ($normalizedField === 'country') {
            return 'fake()->country()';
        }

        if ($normalizedField === 'zip' || $normalizedField === 'zipcode' || $normalizedField === 'postal_code' || $normalizedField === 'postcode') {
            return 'fake()->postcode()';
        }

        if ($normalizedField === 'title') {
            return 'fake()->sentence(3)';
        }

        if ($normalizedField === 'description' || $normalizedField === 'body' || $normalizedField === 'content' || $normalizedField === 'bio') {
            return 'fake()->paragraph()';
        }

        if ($normalizedField === 'slug') {
            return 'fake()->slug()';
        }

        if ($normalizedField === 'url' || $normalizedField === 'website' || str_contains($normalizedField, '_url')) {
            return 'fake()->url()';
        }

        if ($normalizedField === 'image' || str_contains($normalizedField, 'image') || str_contains($normalizedField, 'photo') || str_contains($normalizedField, 'avatar')) {
            return 'fake()->imageUrl()';
        }

        if ($normalizedField === 'uuid' || str_ends_with($normalizedField, '_uuid')) {
            return 'fake()->uuid()';
        }

        if (str_ends_with($normalizedField, '_id')) {
            return '1'; // Foreign key - should be overridden in tests
        }

        if (str_contains($normalizedField, 'price') || str_contains($normalizedField, 'amount') || str_contains($normalizedField, 'cost') || str_contains($normalizedField, 'total')) {
            return 'fake()->randomFloat(2, 10, 1000)';
        }

        if (str_contains($normalizedField, 'quantity') || str_contains($normalizedField, 'count') || str_contains($normalizedField, 'number')) {
            return 'fake()->numberBetween(1, 100)';
        }

        if (str_contains($normalizedField, 'date') || str_ends_with($normalizedField, '_at')) {
            return 'fake()->dateTime()';
        }

        if (str_contains($normalizedField, 'token') || str_contains($normalizedField, 'key')) {
            return 'fake()->sha256()';
        }

        if ($normalizedField === 'ip' || str_contains($normalizedField, 'ip_address')) {
            return 'fake()->ipv4()';
        }

        // Check cast type
        if ($castType) {
            $normalizedCast = strtolower($castType);

            if (str_contains($normalizedCast, 'bool')) {
                return 'fake()->boolean()';
            }

            if (str_contains($normalizedCast, 'int')) {
                return 'fake()->randomNumber()';
            }

            if (str_contains($normalizedCast, 'float') || str_contains($normalizedCast, 'double') || str_contains($normalizedCast, 'decimal')) {
                return 'fake()->randomFloat(2, 0, 1000)';
            }

            if (str_contains($normalizedCast, 'array') || str_contains($normalizedCast, 'json')) {
                return '[]';
            }

            if (str_contains($normalizedCast, 'date') || str_contains($normalizedCast, 'datetime')) {
                return 'fake()->dateTime()';
            }
        }

        // Default to a generic word
        return 'fake()->word()';
    }
}
