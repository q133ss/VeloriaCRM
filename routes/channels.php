<?php

use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('notifications.{userId}', function ($user, int $userId) {
    // Explicit actor-type check: a Client and a User can share the same
    // numeric id, and without this a client's own token could subscribe to
    // a master's notification channel just by matching ids.
    return $user instanceof User && (int) $user->id === (int) $userId;
});

Broadcast::channel('client-notifications.{clientId}', function ($actor, int $clientId) {
    return $actor instanceof Client && (int) $actor->id === $clientId;
});
