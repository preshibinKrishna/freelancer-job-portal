<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelance_contracts', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('engagement_project_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('project_proposal_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('freelancer_id')->constrained('users')->restrictOnDelete();
            $table->decimal('agreed_amount', 14, 2);
            $table->char('currency', 3);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('terms');
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['client_id', 'status', 'created_at']);
            $table->index(['freelancer_id', 'status', 'created_at']);
        });

        Schema::create('contract_milestones', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('freelance_contract_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->decimal('amount', 14, 2)->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('position');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
            $table->index(['freelance_contract_id', 'position']);
        });

        Schema::create('contract_status_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('freelance_contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->timestamp('created_at');
            $table->index(['freelance_contract_id', 'created_at']);
        });

        Schema::create('contract_milestone_status_changes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contract_milestone_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->string('from_status', 20);
            $table->string('to_status', 20);
            $table->timestamp('created_at');
            $table->index(['contract_milestone_id', 'created_at'], 'contract_milestone_status_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_milestone_status_changes');
        Schema::dropIfExists('contract_status_changes');
        Schema::dropIfExists('contract_milestones');
        Schema::dropIfExists('freelance_contracts');
    }
};
