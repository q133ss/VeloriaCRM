<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `clients.user_id` is the MASTER who owns the card: every listing, the unique
 * keys (user_id, phone) / (user_id, email) and the whole Clients page read it
 * that way. `orders.client_id`, on the other hand, is the CLIENT's own user
 * account. Nothing connected the two, so OrderController tried to bridge the gap
 * by reading clients.user_id as if it were the client, which produced cards the
 * master could never see.
 *
 * This adds the missing link. `client_user_id` points at the client's user
 * account, leaving `user_id` to mean the owner, as the rest of the app assumes.
 * Existing cards are matched to accounts by normalised phone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->foreignId('client_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        $this->backfillFromPhones();
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('client_user_id');
        });
    }

    /**
     * Phone is the only shared identifier between a card and an account, and it
     * is stored inconsistently ("+7 916 111-22-33" against "+79161112233"), so
     * both sides are reduced to digits before matching. An 11-digit Russian
     * number starting with 8 is normalised to 7 so both spellings meet.
     */
    private function backfillFromPhones(): void
    {
        $usersByPhone = [];

        foreach (DB::table('users')->select('id', 'phone')->whereNotNull('phone')->cursor() as $user) {
            $key = $this->normalise($user->phone);

            // A phone shared by several accounts cannot identify one of them.
            if ($key === null) {
                continue;
            }

            $usersByPhone[$key] = array_key_exists($key, $usersByPhone) ? false : $user->id;
        }

        foreach (DB::table('clients')->select('id', 'user_id', 'phone')->cursor() as $client) {
            $key = $this->normalise($client->phone);

            if ($key === null || empty($usersByPhone[$key])) {
                continue;
            }

            // The master's own account is never the client on their own card.
            if ($usersByPhone[$key] === $client->user_id) {
                continue;
            }

            DB::table('clients')
                ->where('id', $client->id)
                ->update(['client_user_id' => $usersByPhone[$key]]);
        }
    }

    private function normalise(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        if ($digits === '' || strlen($digits) < 10) {
            return null;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $digits = '7' . substr($digits, 1);
        }

        return $digits;
    }
};
