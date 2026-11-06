<?php

use App\Support\TranslationImporter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Version 1.1.0: German, English and Hungarian.
 * Adds translation keys (links between language versions) and imports the shipped translations
 * for existing installations. Existing Romanian content and admin edits are not modified.
 */
return new class extends Migration
{
    private array $tables = ['pages', 'services', 'projects', 'faqs', 'capabilities', 'industries', 'documents'];

    public function up(): void
    {
        foreach ($this->tables as $t) {
            if (! Schema::hasColumn($t, 'translation_key')) {
                Schema::table($t, function (Blueprint $table) {
                    $table->string('translation_key', 120)->nullable()->index();
                });
            }
        }
        if (! Schema::hasColumn('media', 'alt_i18n')) {
            Schema::table('media', fn (Blueprint $table) => $table->json('alt_i18n')->nullable());
        }
        \App\Models\Setting::flush();
        (new TranslationImporter)->run();
    }

    public function down(): void
    {
        foreach (['de', 'en', 'hu'] as $loc) {
            \App\Models\Page::where('locale', $loc)->delete();
            foreach ([\App\Models\Service::class, \App\Models\Faq::class, \App\Models\Capability::class, \App\Models\Industry::class, \App\Models\MenuItem::class] as $m) {
                $m::where('locale', $loc)->delete();
            }
        }
        if (Schema::hasColumn('media', 'alt_i18n')) {
            Schema::table('media', fn (Blueprint $table) => $table->dropColumn('alt_i18n'));
        }
        foreach ($this->tables as $t) {
            if (Schema::hasColumn($t, 'translation_key')) {
                Schema::table($t, fn (Blueprint $table) => $table->dropColumn('translation_key'));
            }
        }
    }
};
