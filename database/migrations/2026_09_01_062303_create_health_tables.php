<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Health\Models\HealthCheckResultHistoryItem;

return new class extends Migration
{
    public function up(): void
    {
        // The stub read the connection from the model but the table name from
        // EloquentHealthResultStore::getHistoryItemInstance(), which is typed
        // as plain object. Taking both from the model is consistent with what
        // the stub already assumed, and actually typed.
        $historyItem = new HealthCheckResultHistoryItem;

        $connection = $historyItem->getConnectionName();
        $tableName = $historyItem->getTable();

        Schema::connection($connection)->create($tableName, function (Blueprint $table): void {
            $table->id();

            $table->string('check_name');
            $table->string('check_label');
            $table->string('status');
            $table->text('notification_message')->nullable();
            $table->string('short_summary')->nullable();
            $table->json('meta');
            $table->timestamp('ended_at');
            $table->uuid('batch');

            $table->timestamps();
        });

        Schema::connection($connection)->table($tableName, function (Blueprint $table): void {
            $table->index('created_at');
            $table->index('batch');
        });
    }
};
