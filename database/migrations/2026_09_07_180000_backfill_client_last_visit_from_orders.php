<?php

use App\Services\Clients\ClientVisitStats;
use Illuminate\Database\Migrations\Migration;

/**
 * `clients.last_visit_at` was only ever writable by hand, from a field on the
 * client form, so in practice it was null for everyone. Every reader of it — the
 * client list, the analytics, the warm-up groups, the waiting list — has been
 * answering from that null. This fills the column in from the bookings that were
 * there all along; from here the observer and the nightly command keep it right.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(ClientVisitStats::class)->refreshAll();
    }

    public function down(): void
    {
        // Nothing to undo: the column is a projection of the orders, and putting
        // the nulls back would only make the product lie again.
    }
};
