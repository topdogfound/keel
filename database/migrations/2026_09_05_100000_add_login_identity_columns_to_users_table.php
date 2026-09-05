<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('google_id')->nullable()->unique()->after('email_verified_at');
            $table->string('github_id')->nullable()->unique()->after('google_id');
            $table->string('avatar')->nullable()->after('github_id');
            $table->string('avatar_path')->nullable()->after('avatar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['google_id']);
            $table->dropUnique(['github_id']);
            $table->dropColumn(['phone', 'google_id', 'github_id', 'avatar', 'avatar_path']);
        });
    }
};
