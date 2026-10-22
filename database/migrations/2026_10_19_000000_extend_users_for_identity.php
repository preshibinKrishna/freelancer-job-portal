<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->ulid('public_id')->unique()->after('id');
            $table->string('status', 24)->default('active')->index()->after('password');
            $table->unsignedInteger('session_version')->default(0)->after('status');
            $table->string('timezone', 64)->default('UTC')->after('status');
            $table->string('locale', 12)->default('en')->after('timezone');
            $table->text('two_factor_secret')->nullable()->after('locale');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['public_id']);
            $table->dropIndex(['status']);
            $table->dropColumn(['public_id', 'status', 'session_version', 'timezone', 'locale', 'two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at', 'deleted_at']);
        });
    }
};
