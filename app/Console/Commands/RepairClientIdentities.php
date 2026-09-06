<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use App\Services\ClientIdentityService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Repairs client cards left behind by the old pairing logic.
 *
 * Booking by phone used to write `Client::updateOrCreate(['user_id' => $clientUser->id])`,
 * reading `clients.user_id` as the client rather than the master who owns the
 * card. Those rows are invisible on the Clients page and hold the visit history
 * the master's own card is missing.
 *
 * Reports by default and changes nothing until `--apply` is passed, because the
 * right merge depends on data this command cannot see in advance.
 */
class RepairClientIdentities extends Command
{
    protected $signature = 'clients:repair-identities {--apply : Write the changes instead of only reporting them}';

    protected $description = 'Link client cards to their accounts and merge cards left over from the old booking logic';

    public function handle(ClientIdentityService $identity): int
    {
        $apply = (bool) $this->option('apply');

        if (! $apply) {
            $this->warn('Dry run. Nothing is written. Pass --apply to make the changes.');
        }

        $masterIds = Order::query()->distinct()->pluck('master_id')
            ->merge(Client::query()->distinct()->pluck('user_id'))
            ->unique()
            ->filter()
            ->values();

        $linked = 0;
        $merged = 0;
        $orphaned = 0;

        foreach ($masterIds as $masterId) {
            $cards = Client::where('user_id', $masterId)->get();

            // Cards created under the old logic sit against the client's own
            // account id, so they surface as cards "owned" by someone who is not
            // a master here. They are recognised by having no orders of their own.
            $isMaster = Order::where('master_id', $masterId)->exists();

            foreach ($cards as $card) {
                if ($card->client_user_id) {
                    continue;
                }

                $account = $this->findAccount($card, $identity);

                if (! $account) {
                    $orphaned++;

                    continue;
                }

                $duplicate = Client::where('user_id', $masterId)
                    ->where('client_user_id', $account->id)
                    ->where('id', '!=', $card->id)
                    ->first();

                if ($duplicate) {
                    $this->line(sprintf(
                        '  merge card #%d "%s" into #%d (account #%d)',
                        $card->id,
                        $card->name,
                        $duplicate->id,
                        $account->id,
                    ));

                    if ($apply) {
                        $this->mergeInto($duplicate, $card);
                    }

                    $merged++;

                    continue;
                }

                $this->line(sprintf(
                    '  link card #%d "%s" to account #%d%s',
                    $card->id,
                    $card->name,
                    $account->id,
                    $isMaster ? '' : ' (owner has no orders, check this one by hand)',
                ));

                if ($apply) {
                    $card->forceFill(['client_user_id' => $account->id])->save();
                }

                $linked++;
            }
        }

        $this->newLine();
        $this->info(sprintf('Linked: %d. Merged: %d. No account found: %d.', $linked, $merged, $orphaned));

        if (! $apply && ($linked || $merged)) {
            $this->warn('Re-run with --apply to write these changes.');
        }

        return self::SUCCESS;
    }

    private function findAccount(Client $card, ClientIdentityService $identity): ?User
    {
        $digits = preg_replace('/\D+/', '', (string) $card->phone);

        if ($digits !== '') {
            $matches = User::whereRaw("regexp_replace(coalesce(phone, ''), '\\D', '', 'g') = ?", [$digits])
                ->where('id', '!=', $card->user_id)
                ->get();

            // One phone shared by several accounts cannot pick one of them.
            if ($matches->count() === 1) {
                return $matches->first();
            }
        }

        if ($card->email) {
            return User::where('email', $card->email)->where('id', '!=', $card->user_id)->first();
        }

        return null;
    }

    /**
     * Keeps whichever value is actually filled in. The abandoned card usually
     * holds the notes, the surviving one the visit history, and neither should
     * lose what it has.
     */
    private function mergeInto(Client $target, Client $source): void
    {
        DB::transaction(function () use ($target, $source) {
            foreach (['name', 'email', 'birthday', 'notes', 'loyalty_level', 'tags', 'allergies', 'preferences'] as $field) {
                if (blank($target->{$field}) && filled($source->{$field})) {
                    $target->{$field} = $source->{$field};
                }
            }

            if ($source->last_visit_at && (! $target->last_visit_at || $source->last_visit_at->gt($target->last_visit_at))) {
                $target->last_visit_at = $source->last_visit_at;
            }

            $target->save();
            $source->delete();
        });
    }
}
