<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'avatar_path')) {
                $table->string('avatar_path')->nullable()->after('password');
            }
            if (! Schema::hasColumn('users', 'department_id')) {
                $table->foreignUlid('department_id')->nullable()->constrained('departments')->nullOnDelete()->after('avatar_path');
            }
            if (! Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title')->nullable()->after('department_id');
            }
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable()->after('job_title');
            }
            if (! Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('phone');
            }
            if (! Schema::hasColumn('users', 'signature_path')) {
                $table->string('signature_path')->nullable()->after('is_active');
            }
            if (! Schema::hasColumn('users', 'signature_image_path')) {
                $table->string('signature_image_path')->nullable()->after('signature_path');
            }
            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active')->after('signature_image_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_path', 'department_id', 'job_title', 'phone', 'is_active', 'signature_path', 'signature_image_path', 'status']);
        });
    }
};
