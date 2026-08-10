<?php

namespace Database\Factories\Domains\Documents\Models;

use App\Domains\Documents\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'document_number' => strtoupper(Str::random(12)),
            'reference' => $this->faker->optional()->word(),
            'subject' => $this->faker->sentence(4),
            'document_type' => 'note',
            'author_id' => \App\Domains\Users\Models\User::factory(),
            'department_id' => null,
            'version' => '1.0',
            'status' => 'draft',
            'confidentiality' => 'interne',
            'document_date' => now(),
            'content' => $this->faker->optional()->text(),
            'hash' => hash('sha256', $this->faker->text(50)),
            'qr_code_path' => null,
            'is_archived' => false,
            'is_deleted' => false,
        ];
    }
}

