<?php

namespace Database\Factories\Domains\Workflows\Models;

use App\Domains\Workflows\Models\Workflow;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkflowFactory extends Factory
{
    protected $model = Workflow::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->sentence(),
            'document_type' => 'note',
            'steps' => [
                ['name' => 'chef', 'role' => 'admin', 'order' => 1],
            ],
'is_active' => true,
            'created_by' => $this->faker->randomNumber(5, true),
        ];
    }
}

