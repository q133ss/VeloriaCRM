<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A person a master works with exists twice in this product.
 *
 * `users` is the account bookings hang off: `orders.client_id` points there, and
 * it is what the client portal and the Telegram bot log into. `clients` is the
 * master's own card, holding notes, allergies, birthday and loyalty, owned via
 * `clients.user_id` and unique per (owner, phone).
 *
 * Both are needed and neither replaces the other, but until now each was created
 * on its own: booking by phone made an account, the Clients page made a card, and
 * nothing tied them together. This service is the single place that creates the
 * pair and keeps them linked through `clients.client_user_id`.
 */
class ClientIdentityService
{
    /**
     * Find or create the account and the master's card for one person, link them,
     * and return both. Existing records are reused and never duplicated: the card
     * is matched first by its link, then by the normalised phone the master
     * already has on file.
     *
     * @return array{user: User, card: Client}
     */
    public function resolve(
        int $masterId,
        string $phone,
        ?string $name = null,
        ?string $email = null,
        array $cardAttributes = [],
    ): array {
        $phone = $this->normalisePhone($phone);
        $email = $this->normaliseEmail($email);

        return DB::transaction(function () use ($masterId, $phone, $name, $email, $cardAttributes) {
            $user = $this->resolveAccount($phone, $name, $email);
            $card = $this->resolveCard($masterId, $user, $phone, $name, $email, $cardAttributes);

            return ['user' => $user, 'card' => $card];
        });
    }

    /**
     * The master's card for a person who already has an account. Used when
     * reading an order: `orders.client_id` gives the account, the card holds
     * everything the master actually wrote down.
     */
    public function cardFor(int $masterId, ?int $clientUserId, ?string $phone = null): ?Client
    {
        if ($clientUserId) {
            $card = Client::where('user_id', $masterId)
                ->where('client_user_id', $clientUserId)
                ->first();

            if ($card) {
                return $card;
            }
        }

        if ($phone === null || $phone === '') {
            return null;
        }

        return $this->findCardByPhone($masterId, $this->normalisePhone($phone));
    }

    /**
     * Cards for a whole page of orders, keyed by the account id, so a list never
     * queries per row.
     *
     * @param  iterable<Order>  $orders
     * @return \Illuminate\Support\Collection<int, Client>
     */
    public function cardsForOrders(int $masterId, iterable $orders)
    {
        $accountIds = collect($orders)
            ->map(fn (Order $order) => $order->client_id)
            ->filter()
            ->unique()
            ->values();

        if ($accountIds->isEmpty()) {
            return collect();
        }

        return Client::where('user_id', $masterId)
            ->whereIn('client_user_id', $accountIds)
            ->get()
            ->keyBy('client_user_id');
    }

    /**
     * An account whose card the master has not linked yet. Booking flows create
     * the account from a phone number, so the account, not the card, is the
     * anchor here.
     */
    private function resolveAccount(string $phone, ?string $name, ?string $email): User
    {
        $user = User::where('phone', $phone)->first();

        if (! $user && $email) {
            $user = User::where('email', $email)->first();
        }

        if (! $user) {
            return User::create([
                'name' => $name ?: 'Клиент ' . Str::substr($phone, -4),
                'phone' => $phone,
                'email' => $email,
                'password' => Str::random(16),
            ]);
        }

        // Only fill gaps. A client who set their own name in the portal should
        // not have it overwritten by whatever the master typed this time.
        $user->forceFill([
            'name' => $user->name ?: ($name ?: $user->name),
            'email' => $user->email ?: $email,
            'phone' => $phone,
        ])->save();

        return $user;
    }

    private function resolveCard(
        int $masterId,
        User $user,
        string $phone,
        ?string $name,
        ?string $email,
        array $cardAttributes,
    ): Client {
        $card = Client::where('user_id', $masterId)
            ->where('client_user_id', $user->id)
            ->first();

        // A card the master typed in by hand before this person was ever booked
        // has no link yet. Adopt it instead of creating a second one, which the
        // (user_id, phone) unique key would reject anyway.
        $card ??= $this->findCardByPhone($masterId, $phone);

        $attributes = array_merge($cardAttributes, [
            'client_user_id' => $user->id,
            'phone' => $phone,
        ]);

        if ($card) {
            $attributes['name'] = $cardAttributes['name'] ?? ($name ?: $card->name);
            $attributes['email'] = $cardAttributes['email'] ?? ($card->email ?: $email);

            $card->fill($attributes)->save();

            return $card;
        }

        return Client::create(array_merge($attributes, [
            'user_id' => $masterId,
            'name' => $cardAttributes['name'] ?? ($name ?: $user->name),
            'email' => $cardAttributes['email'] ?? $email,
        ]));
    }

    /**
     * Phones are stored in whatever shape they were typed, so matching compares
     * digits rather than strings.
     */
    private function findCardByPhone(int $masterId, string $phone): ?Client
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if ($digits === '') {
            return null;
        }

        return Client::where('user_id', $masterId)
            ->whereRaw("regexp_replace(phone, '\\D', '', 'g') = ?", [$digits])
            ->first();
    }

    public function normalisePhone(string $phone): string
    {
        $digits = preg_replace('/[^0-9]+/', '', $phone);

        if (! $digits) {
            return trim($phone);
        }

        if (strlen($digits) === 10) {
            $digits = '7' . $digits;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '8')) {
            $digits = '7' . substr($digits, 1);
        }

        if (! str_starts_with($digits, '7')) {
            $digits = '7' . $digits;
        }

        return '+' . $digits;
    }

    private function normaliseEmail(?string $email): ?string
    {
        $email = $email !== null ? trim($email) : null;

        return $email === '' ? null : $email;
    }
}
