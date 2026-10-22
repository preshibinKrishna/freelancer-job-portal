<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_plans', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('code', 80)->unique();
            $table->string('name', 160);
            $table->string('category', 40)->index();
            $table->text('description')->nullable();
            $table->char('currency', 3);
            $table->decimal('amount', 14, 2);
            $table->string('interval', 16)->nullable();
            $table->unsignedSmallInteger('interval_count')->default(1);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('billing_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('billing_plan_id')->constrained()->restrictOnDelete();
            $table->morphs('subscriber');
            $table->string('status', 20)->default('pending')->index();
            $table->string('currency', 3);
            $table->decimal('amount', 14, 2);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('provider', 60)->nullable();
            $table->string('provider_reference', 180)->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['subscriber_type', 'subscriber_id', 'status']);
        });

        Schema::create('billing_invoices', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('number', 40)->unique();
            $table->foreignId('billing_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('bill_to');
            $table->string('status', 20)->default('draft')->index();
            $table->char('currency', 3);
            $table->decimal('subtotal', 14, 2);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['bill_to_type', 'bill_to_id', 'created_at']);
        });

        Schema::create('billing_invoice_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('billing_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('description', 240);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('unit_amount', 14, 2);
            $table->decimal('amount', 14, 2);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index('billing_invoice_id');
        });

        Schema::create('billing_transactions', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('billing_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('billing_subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20);
            $table->string('status', 20)->default('pending')->index();
            $table->char('currency', 3);
            $table->decimal('amount', 14, 2);
            $table->string('idempotency_key', 120)->unique();
            $table->string('provider', 60)->nullable();
            $table->string('provider_reference', 180)->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('succeeded_at')->nullable();
            $table->timestamps();
            $table->index(['billing_invoice_id', 'created_at']);
            $table->index(['billing_subscription_id', 'created_at']);
        });

        Schema::create('billing_refunds', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('billing_transaction_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending')->index();
            $table->char('currency', 3);
            $table->decimal('amount', 14, 2);
            $table->string('reason', 240);
            $table->string('idempotency_key', 120)->unique();
            $table->string('provider_reference', 180)->nullable()->unique();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('billing_payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('billing_transaction_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt_number');
            $table->string('status', 20)->default('created')->index();
            $table->string('provider', 60)->nullable();
            $table->string('provider_reference', 180)->nullable()->unique();
            $table->string('failure_code', 100)->nullable();
            $table->string('idempotency_key', 120)->unique();
            $table->json('metadata')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamps();
            $table->unique(['billing_transaction_id', 'attempt_number'], 'billing_attempt_transaction_number_unique');
        });

        Schema::create('billing_audit_entries', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type', 100);
            $table->unsignedBigInteger('subject_id');
            $table->string('event', 100);
            $table->string('idempotency_key', 120)->unique();
            $table->json('safe_metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['subject_type', 'subject_id', 'created_at']);
        });

        Schema::create('billing_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->ulid('public_id')->unique();
            $table->string('provider', 60);
            $table->string('provider_event_id', 180);
            $table->string('event_type', 120);
            $table->string('status', 20)->default('received')->index();
            $table->json('payload');
            $table->timestamp('received_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
            $table->text('failure_summary')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'provider_event_id']);
            $table->index(['status', 'received_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_webhook_events');
        Schema::dropIfExists('billing_audit_entries');
        Schema::dropIfExists('billing_payment_attempts');
        Schema::dropIfExists('billing_refunds');
        Schema::dropIfExists('billing_transactions');
        Schema::dropIfExists('billing_invoice_items');
        Schema::dropIfExists('billing_invoices');
        Schema::dropIfExists('billing_subscriptions');
        Schema::dropIfExists('billing_plans');
    }
};
