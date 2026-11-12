<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Uploaded Excel workbooks; only a version validated against Excel's own results can be activated.
        Schema::create('calc_model_versions', function (Blueprint $table) {
            $table->id();
            $table->string('model_key', 80);
            $table->string('label', 60)->nullable();
            $table->string('original_name');
            $table->string('path');
            $table->string('sha256', 64);
            $table->unsignedBigInteger('size');
            $table->string('status', 20)->default('respins');
            $table->unsignedInteger('formulas')->default(0);
            $table->unsignedInteger('mismatches')->default(0);
            $table->json('report')->nullable();
            $table->foreignId('uploaded_by')->nullable();
            $table->foreignId('activated_by')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
            $table->index(['model_key', 'status']);
            $table->unique(['model_key', 'sha256']);
        });

        // Versioned overrides of parameter cells (tariffs, coefficients) on top of a workbook version.
        Schema::create('calc_parameter_sets', function (Blueprint $table) {
            $table->id();
            $table->string('model_key', 80);
            $table->foreignId('calc_model_version_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('overrides');
            $table->string('note')->nullable();
            $table->boolean('is_active')->default(false);
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
            $table->unique(['calc_model_version_id', 'version']);
        });

        Schema::create('calculations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->string('title');
            $table->string('model_key', 80);
            $table->foreignId('calc_model_version_id')->constrained()->restrictOnDelete();
            $table->foreignId('calc_parameter_set_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rfq_id')->nullable()->constrained()->nullOnDelete();
            $table->json('inputs');
            $table->string('variant', 40)->nullable();
            $table->string('state', 10)->default('block');
            $table->string('status_text')->nullable();
            $table->decimal('hours', 14, 6)->nullable();
            $table->decimal('total', 16, 4)->nullable();
            $table->string('currency', 3)->nullable();
            // Frozen at save time: results, parameter overrides, machine rate, engine version.
            $table->json('results')->nullable();
            $table->date('calc_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['model_key', 'created_at']);
        });

        // Dies, moulds and special tooling (FEPO).
        Schema::create('tool_projects', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->string('name');
            $table->string('type', 30)->default('stanta_simpla');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rfq_id')->nullable()->constrained()->nullOnDelete();
            $table->string('complexity', 20)->nullable();
            $table->unsignedInteger('operations_count')->nullable();
            $table->string('lead_time', 80)->nullable();
            $table->unsignedInteger('amortization_quantity')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tool_cost_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_project_id')->constrained()->cascadeOnDelete();
            $table->string('phase', 20)->default('realizare');
            $table->string('category', 40);
            $table->string('description')->nullable();
            $table->decimal('quantity', 14, 4)->default(0);
            $table->string('unit', 10)->default('h');
            $table->decimal('unit_cost', 14, 4)->nullable();
            $table->foreignId('machine_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('calculation_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('product_tool_project', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tool_project_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_id', 'tool_project_id']);
        });

        // Electroplating requests: data is captured now, the cost formula waits for validated production data.
        Schema::create('zinc_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 30)->unique();
            $table->string('title');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rfq_id')->nullable()->constrained()->nullOnDelete();
            $table->json('inputs');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30);
            $table->unsignedSmallInteger('revision')->default(0);
            $table->foreignId('root_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->boolean('is_current')->default(true);
            $table->foreignId('entity_id')->constrained()->restrictOnDelete();
            $table->foreignId('company_id')->constrained()->restrictOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rfq_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20)->default('piese');
            $table->string('status', 30)->default('ciorna');
            $table->string('title')->nullable();
            $table->string('language', 5)->default('ro');
            $table->string('currency', 3)->default('EUR');
            $table->decimal('exchange_rate', 12, 6)->nullable();
            $table->date('exchange_rate_date')->nullable();
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->date('issued_at')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('lead_time', 160)->nullable();
            $table->string('payment_terms')->nullable();
            $table->string('delivery_terms')->nullable();
            $table->string('incoterm', 10)->nullable();
            $table->string('min_order', 160)->nullable();
            $table->text('intro')->nullable();
            $table->text('notes')->nullable();
            $table->text('internal_notes')->nullable();
            $table->decimal('discount_percent', 6, 3)->default(0);
            $table->decimal('subtotal', 16, 2)->default(0);
            $table->decimal('total_net', 16, 2)->default(0);
            $table->decimal('vat_amount', 16, 2)->default(0);
            $table->decimal('total_gross', 16, 2)->default(0);
            $table->decimal('cost_total', 16, 2)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->decimal('confirmed_revenue', 16, 2)->nullable();
            // Everything needed to reprint the issued document unchanged: issuer, client, lines, terms, rates.
            $table->json('snapshot')->nullable();
            $table->string('public_token_hash', 64)->nullable()->unique();
            $table->timestamp('public_expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['number', 'revision']);
            $table->index(['status', 'issued_at']);
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(1);
            $table->string('kind', 20)->default('piesa');
            $table->string('code', 80)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 14, 3)->default(1);
            $table->string('unit', 10)->default('buc');
            $table->decimal('unit_cost', 16, 4)->nullable();
            $table->decimal('unit_price', 16, 4)->default(0);
            $table->decimal('discount_percent', 6, 3)->default(0);
            $table->decimal('line_total', 16, 2)->default(0);
            $table->boolean('is_optional')->default(false);
            $table->string('lead_time', 160)->nullable();
            $table->foreignId('calculation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tool_project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->json('price_breaks')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('quote_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Commercial texts per issuing entity and language (terms, email body, signature).
        Schema::create('quote_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entity_id')->constrained()->cascadeOnDelete();
            $table->string('language', 5);
            $table->string('title')->nullable();
            $table->text('intro')->nullable();
            $table->text('terms')->nullable();
            $table->text('footer')->nullable();
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->text('signature')->nullable();
            $table->timestamps();
            $table->unique(['entity_id', 'language']);
        });

        Schema::create('sequences', function (Blueprint $table) {
            $table->string('name', 60);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('value')->default(0);
            $table->primary(['name', 'year']);
        });
    }

    public function down(): void
    {
        foreach (['sequences', 'quote_templates', 'quote_events', 'quote_items', 'quotes', 'zinc_jobs', 'product_tool_project', 'tool_cost_lines', 'tool_projects', 'calculations', 'calc_parameter_sets', 'calc_model_versions'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
