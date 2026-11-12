<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Companies of the group: brands shown on the public site and legal entities that issue quotes.
        Schema::create('entities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('activity')->nullable();
            $table->text('description')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->boolean('logo_on_dark')->default(false);
            $table->string('accent_color', 9)->nullable();
            $table->boolean('show_on_site')->default(true);
            $table->boolean('is_issuer')->default(false);
            // Legal identity – empty until an administrator enters and confirms it.
            $table->string('legal_name')->nullable();
            $table->string('tax_id', 40)->nullable();
            $table->string('reg_no', 40)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('county', 120)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->default('RO');
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->string('bank')->nullable();
            $table->string('iban', 40)->nullable();
            $table->string('quote_prefix', 10)->nullable();
            $table->timestamp('legal_confirmed_at')->nullable();
            $table->foreignId('legal_confirmed_by')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('kind', 20)->default('client');
            $table->string('country', 2)->nullable();
            $table->string('county', 120)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('tax_id', 40)->nullable();
            $table->string('reg_no', 40)->nullable();
            $table->string('industry', 120)->nullable();
            $table->string('website')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('language', 5)->default('ro');
            $table->string('source', 60)->nullable();
            $table->json('interests')->nullable();
            $table->string('stage', 30)->default('lead');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('do_not_contact')->default(false);
            $table->string('lawful_basis', 40)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['stage', 'next_follow_up_at']);
            $table->index('name');
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('first_name', 120)->nullable();
            $table->string('last_name', 120);
            $table->string('position', 160)->nullable();
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('mobile', 40)->nullable();
            $table->string('language', 5)->default('ro');
            $table->boolean('is_primary')->default(false);
            $table->boolean('do_not_contact')->default(false);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index('email');
        });

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category', 120)->nullable();
            $table->string('country', 2)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('tax_id', 40)->nullable();
            $table->string('website')->nullable();
            $table->string('contact_name', 160)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('payment_terms', 160)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->string('category', 40)->default('otel');
            $table->string('grade', 80)->nullable();
            $table->string('form', 60)->nullable();
            $table->decimal('density', 10, 4)->nullable();
            $table->string('unit', 10)->default('kg');
            $table->decimal('price', 14, 4)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->date('price_date')->nullable();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('price_history', function (Blueprint $table) {
            $table->id();
            $table->string('priceable_type', 60);
            $table->unsignedBigInteger('priceable_id');
            $table->string('field', 40);
            $table->decimal('old_value', 14, 4)->nullable();
            $table->decimal('new_value', 14, 4)->nullable();
            $table->string('currency', 3)->nullable();
            $table->foreignId('user_id')->nullable();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['priceable_type', 'priceable_id']);
        });

        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->foreignId('entity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('process', 80)->nullable();
            $table->string('calc_model_key', 80)->nullable();
            // Hourly rate stays empty until a person with the "rates" permission enters it.
            $table->decimal('hourly_rate', 14, 4)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->date('rate_date')->nullable();
            $table->text('capacity')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('operations', function (Blueprint $table) {
            $table->id();
            $table->string('code', 60)->unique();
            $table->string('name');
            $table->string('category', 60)->nullable();
            $table->foreignId('machine_id')->nullable()->constrained()->nullOnDelete();
            $table->string('cost_basis', 20)->default('ora');
            $table->string('calc_model_key', 80)->nullable();
            $table->decimal('rate', 14, 4)->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->boolean('is_subcontracted')->default(false);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 20)->default('piesa');
            $table->string('code', 80);
            $table->string('name');
            $table->string('project')->nullable();
            $table->string('drawing_no', 80)->nullable();
            $table->string('revision', 20)->nullable();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('thickness', 10, 3)->nullable();
            $table->decimal('length', 10, 3)->nullable();
            $table->decimal('width', 10, 3)->nullable();
            $table->decimal('height', 10, 3)->nullable();
            $table->decimal('weight_gross', 12, 4)->nullable();
            $table->decimal('weight_net', 12, 4)->nullable();
            $table->unsignedInteger('annual_quantity')->nullable();
            $table->unsignedInteger('lot_size')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['company_id', 'code']);
        });

        Schema::create('rfqs', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('entity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('services')->nullable();
            $table->string('source', 60)->nullable();
            $table->string('status', 30)->default('noua');
            $table->string('priority', 20)->default('normala');
            $table->date('received_at')->nullable();
            $table->date('due_at')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('engineer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lost_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'due_at']);
        });

        Schema::create('rfq_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rfq_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->string('service', 20)->default('piese');
            $table->string('part_code', 80)->nullable();
            $table->string('part_name');
            $table->string('drawing_no', 80)->nullable();
            $table->string('revision', 20)->nullable();
            $table->string('material', 120)->nullable();
            $table->decimal('quantity', 14, 3)->nullable();
            $table->decimal('annual_quantity', 14, 3)->nullable();
            $table->decimal('lot_size', 14, 3)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject_type', 60)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->default('nota');
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
            $table->index(['subject_type', 'subject_id']);
            $table->index(['assigned_to', 'done_at', 'due_at']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('documentable_type', 60);
            $table->unsignedBigInteger('documentable_id');
            $table->string('category', 40)->default('altele');
            $table->string('original_name');
            $table->string('path');
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->string('sha256', 64);
            $table->boolean('client_visible')->default(false);
            $table->foreignId('uploaded_by')->nullable();
            $table->timestamps();
            $table->index(['documentable_type', 'documentable_id']);
        });

        // Global exclusion list for commercial email (addresses stored as SHA-256 of the lower-cased address).
        Schema::create('suppressions', function (Blueprint $table) {
            $table->id();
            $table->string('email_hash', 64)->unique();
            $table->string('reason', 40)->default('dezabonare');
            $table->string('source', 60)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        foreach (['suppressions', 'documents', 'activities', 'rfq_items', 'rfqs', 'products', 'operations', 'machines', 'price_history', 'materials', 'suppliers', 'contacts', 'companies', 'entities'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
