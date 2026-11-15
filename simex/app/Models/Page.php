<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * Compatibility shim for updating from version 0.1.0 only.
 *
 * The updater shipped in 0.1.0 runs a fallback check after an update, on hosts that block HTTP
 * requests to their own domain: it asks this class for a "published page" (a leftover from the
 * website it was derived from). Version 0.1.0 has no such class, so the update was rolled back.
 * Answering with the group's companies lets that old updater finish. Nothing else uses this class;
 * it can be removed once no installation is still on 0.1.0.
 */
class Page
{
    public static function where(mixed ...$ignored): Builder
    {
        return Entity::query();
    }
}
