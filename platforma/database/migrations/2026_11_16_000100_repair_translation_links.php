<?php

use App\Support\TranslationImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Version 1.1.1: links in the German, English and Hungarian menus and page sections imported by 1.1.0
 * were saved without the language prefix ("/leistungen" instead of "/de/leistungen"). Adds the prefix.
 * Only exact links to a page or service of the same language are changed; nothing else is touched.
 */
return new class extends Migration
{
    public function up(): void
    {
        (new TranslationImporter)->repairLinks();
    }

    public function down(): void
    {
        // Nothing to undo: the repaired links are the correct ones.
    }
};
