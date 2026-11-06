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
            $table->string('key', 120)->unique();
            $table->longText('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 100);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt')->nullable();
            $table->string('caption')->nullable();
            $table->string('folder', 60)->default('general');
            $table->json('variants')->nullable();
            $table->string('focal', 20)->default('50% 50%');
            $table->foreignId('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5)->default('ro');
            $table->string('slug', 120);
            $table->string('template', 40)->default('standard');
            $table->string('title');
            $table->string('nav_title')->nullable();
            $table->text('lead')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->foreignId('og_image_id')->nullable();
            $table->boolean('noindex')->default(false);
            $table->json('published_data')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('published_by')->nullable();
            $table->boolean('has_unpublished_changes')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_system')->default(false);
            $table->timestamps();
            $table->unique(['locale', 'slug']);
        });

        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->json('data')->nullable();
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5)->default('ro');
            $table->string('slug', 120);
            $table->string('title');
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('image_id')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->unique(['locale', 'slug']);
        });

        Schema::create('capabilities', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5)->default('ro');
            $table->string('group', 60)->default('linie');
            $table->string('key', 60)->nullable();
            $table->string('label');
            $table->string('value')->nullable();
            $table->string('unit', 30)->nullable();
            $table->text('note')->nullable();
            $table->string('source')->nullable();
            $table->boolean('is_confirmed')->default(false);
            $table->boolean('show_on_home')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('industries', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5)->default('ro');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('image_id')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5)->default('ro');
            $table->string('slug', 120);
            $table->string('title');
            $table->string('client')->nullable();
            $table->boolean('client_consent')->default(false);
            $table->text('summary')->nullable();
            $table->longText('body')->nullable();
            $table->foreignId('cover_id')->nullable();
            $table->json('gallery')->nullable();
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
            $table->unique(['locale', 'slug']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5)->default('ro');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category', 40)->default('document');
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_mime', 100)->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5)->default('ro');
            $table->string('category', 60)->default('general');
            $table->string('question');
            $table->text('answer');
            $table->boolean('is_published')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('locale', 5)->default('ro');
            $table->string('menu', 30)->default('header');
            $table->string('label');
            $table->string('url');
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_button')->default(false);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path', 500);
            $table->string('to_url', 500)->nullable();
            $table->unsignedSmallInteger('status_code')->default(301);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
            $table->index('from_path');
        });
    }

    public function down(): void
    {
        foreach (['redirects', 'menu_items', 'faqs', 'documents', 'projects', 'industries', 'capabilities', 'services', 'sections', 'pages', 'media', 'settings'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
