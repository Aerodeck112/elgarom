<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->longText('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });

        Schema::create('mail_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('template', 60);
            $table->string('to_email');
            $table->string('to_name')->nullable();
            $table->string('subject');
            $table->json('payload')->nullable();
            $table->string('related_type', 60)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'next_attempt_at']);
            $table->index(['related_type', 'related_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('action', 80);
            $table->string('subject_type', 80)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['action', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });

        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('type', 20)->default('complet');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('sha256', 64)->nullable();
            $table->string('status', 20)->default('in_lucru');
            $table->string('app_version', 30)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('system_updates', function (Blueprint $table) {
            $table->id();
            $table->string('version_from', 30);
            $table->string('version_to', 30);
            $table->string('package_sha256', 64);
            $table->string('status', 20);
            $table->longText('log')->nullable();
            $table->text('changelog')->nullable();
            $table->foreignId('backup_id')->nullable();
            $table->foreignId('user_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['system_updates', 'backups', 'audit_logs', 'mail_outbox', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
