<?php

namespace App\Services;

use App\Events\ClientNotificationCreated;
use App\Models\Client;
use App\Models\ClientNotification;
use App\Models\MasterPost;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * The client-app mirror of NotificationService — writes the row, broadcasts
 * it, and (unlike the master version) also calls ExpoPushService, since a
 * backgrounded client app is never woken by the broadcast alone.
 */
class ClientNotificationService
{
    public function __construct(private readonly ExpoPushService $push)
    {
    }

    public function send(int $clientId, string $title, string $message, ?string $actionUrl = null): ClientNotification
    {
        return DB::transaction(function () use ($clientId, $title, $message, $actionUrl) {
            /** @var ClientNotification $notification */
            $notification = ClientNotification::query()->create([
                'client_id' => $clientId,
                'title' => $title,
                'message' => $message,
                'action_url' => $actionUrl,
            ]);

            broadcast(new ClientNotificationCreated($notification))->toOthers();

            return $notification;
        });
    }

    public function sendToClient(Client $client, string $title, string $message, ?string $actionUrl = null): ClientNotification
    {
        $notification = $this->send($client->id, $title, $message, $actionUrl);

        if ($client->expo_push_token) {
            $this->push->send($client->expo_push_token, $title, $message, [
                'action_url' => $actionUrl,
            ]);
        }

        return $notification;
    }

    public function notifyBookingConfirmed(Client $client, string $serviceLabel, Carbon $startsAtLocal): void
    {
        $this->sendToClient(
            $client,
            __('client_portal.notifications.booking_confirmed_title'),
            __('client_portal.notifications.booking_confirmed_message', [
                'service' => $serviceLabel,
                'datetime' => $startsAtLocal->translatedFormat('d.m.Y H:i'),
            ]),
        );
    }

    public function notifyAppointmentReminder(Client $client, string $serviceLabel, Carbon $startsAtLocal): void
    {
        $this->sendToClient(
            $client,
            __('client_portal.notifications.appointment_reminder_title'),
            __('client_portal.notifications.appointment_reminder_message', [
                'service' => $serviceLabel,
                'datetime' => $startsAtLocal->translatedFormat('d.m.Y H:i'),
            ]),
        );
    }

    /**
     * Fans out to every client of the master who published the post — the
     * feed itself (Client\ContentController::posts()) already only shows
     * published posts, so this is the "someone should know right now" half.
     */
    public function notifyMasterPostPublished(MasterPost $post): void
    {
        Client::query()
            ->where('user_id', $post->user_id)
            ->chunkById(200, function ($clients) use ($post) {
                foreach ($clients as $client) {
                    $this->sendToClient(
                        $client,
                        __('client_portal.notifications.new_post_title'),
                        $post->title,
                    );
                }
            });
    }
}
