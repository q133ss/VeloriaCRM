<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The classic landing layout (landings.templates.*) was removed in favour of the
 * full-page templates. Pages still pointing at it move to the default template;
 * the controller would fall back to it anyway, this keeps the stored value valid
 * so the edit form can save the page.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('landings')
            ->where('landing', 'like', 'landings.templates.%')
            ->update(['landing' => 'landings.full.salone']);
    }

    public function down(): void
    {
        // The classic templates are gone; nothing to restore.
    }
};
