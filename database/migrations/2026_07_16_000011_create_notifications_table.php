<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('type'); // info, warning, success, error
            $table->string('title');
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->ulid('notifiable_id');
            $table->string('notifiable_type');
            $table->string('channel')->default('database'); // database, email, sms, push
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_id', 'notifiable_type', 'is_read']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('user_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // document_created, document_signed, workflow_step, etc.
            $table->boolean('email')->default(true);
            $table->boolean('database')->default(true);
            $table->boolean('sms')->default(false);
            $table->boolean('push')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notifications');
    }
};
