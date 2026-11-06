<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->string('kind', 20)->default('oferta');
            $table->string('status', 30)->default('nou');
            $table->foreignId('assignee_id')->nullable();
            $table->string('name');
            $table->string('company')->nullable();
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->string('city')->nullable();
            $table->string('part_type', 60)->nullable();
            $table->string('material', 80)->nullable();
            $table->string('process', 30)->nullable();
            $table->text('description')->nullable();
            $table->string('quantity', 60)->nullable();
            $table->string('weight', 60)->nullable();
            $table->string('dimensions', 120)->nullable();
            $table->date('deadline')->nullable();
            $table->string('deadline_note', 120)->nullable();
            $table->boolean('marketing_consent')->default(false);
            $table->timestamp('privacy_accepted_at')->nullable();
            $table->string('source', 30)->default('formular');
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('locale', 5)->default('ro');
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('quote_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->string('sha256', 64);
            $table->timestamps();
        });

        Schema::create('quote_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable();
            $table->string('type', 30);
            $table->text('body')->nullable();
            $table->json('data')->nullable();
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
        });

        Schema::create('knowledge_entries', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5)->default('ro');
            $table->string('title');
            $table->text('content');
            $table->string('source_label')->nullable();
            $table->string('source_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('session_hash', 64)->index();
            $table->string('ip_hash', 64)->nullable();
            $table->unsignedInteger('messages_count')->default(0);
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->unsignedBigInteger('cost_micro_usd')->default(0);
            $table->boolean('led_to_quote')->default(false);
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->text('content');
            $table->json('raw')->nullable();
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->string('status', 20)->default('ok');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('ai_usage_daily', function (Blueprint $table) {
            $table->date('day')->primary();
            $table->unsignedInteger('requests')->default(0);
            $table->unsignedInteger('refused')->default(0);
            $table->unsignedInteger('errors')->default(0);
            $table->unsignedBigInteger('tokens_in')->default(0);
            $table->unsignedBigInteger('tokens_out')->default(0);
            $table->unsignedBigInteger('cost_micro_usd')->default(0);
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
        foreach (['system_updates', 'backups', 'ai_usage_daily', 'ai_messages', 'ai_conversations', 'knowledge_entries', 'audit_logs', 'mail_outbox', 'quote_events', 'quote_files', 'quote_requests'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
