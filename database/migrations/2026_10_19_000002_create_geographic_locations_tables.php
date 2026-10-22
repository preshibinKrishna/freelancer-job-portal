<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('parent_id')->nullable()->constrained('locations')->restrictOnDelete();
            $table->string('type', 24)->index();
            $table->string('name', 120);
            $table->string('slug', 140);
            $table->string('root_slug', 140)->nullable()->unique();
            $table->char('country_code', 2)->nullable()->unique();
            $table->timestamps();
            $table->unique(['parent_id', 'slug']);
            $table->index(['type', 'name']);
        });

        Schema::create('location_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->string('locationable_type');
            $table->unsignedBigInteger('locationable_id');
            $table->string('context', 32)->default('primary');
            $table->timestamps();
            $table->unique(['locationable_type', 'locationable_id', 'context'], 'location_assignment_owner_context_unique');
            $table->index(['locationable_type', 'locationable_id']);
            $table->index(['location_id', 'context']);
        });

        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->foreignId('location_id')->nullable()->after('user_id')->constrained('locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('location_id');
        });
        Schema::dropIfExists('location_assignments');
        Schema::dropIfExists('locations');
    }
};
