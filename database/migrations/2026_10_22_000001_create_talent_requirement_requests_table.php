<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_requirement_requests', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('category', 60);
            $table->string('solution_type', 60);
            $table->json('details');
            $table->json('suggested_skills');
            $table->timestamp('submitted_at');
            $table->timestamps();
            $table->index(['company_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_requirement_requests');
    }
};
