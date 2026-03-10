<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;
use App\Services\Admin\AdminAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminSupportTicketController extends Controller
{
    public function __construct(
        private readonly AdminAuditService $auditService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'all');

        $tickets = SupportTicket::query()
            ->with(['user:id,name,email', 'messages' => fn ($query) => $query->latest()->limit(1), 'assignee:id,name'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByRaw('case when status in (?, ?) then 0 else 1 end', [
                SupportTicket::STATUS_WAITING,
                SupportTicket::STATUS_OPEN,
            ])
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get()
            ->map(fn (SupportTicket $ticket) => $this->transformTicketSummary($ticket))
            ->all();

        return response()->json([
            'data' => $tickets,
            'operators' => User::query()
                ->where('is_admin', true)
                ->whereIn('admin_role', [User::ADMIN_ROLE_SUPER_ADMIN, User::ADMIN_ROLE_SUPPORT])
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (User $user) => ['id' => $user->id, 'name' => $user->name])
                ->all(),
        ]);
    }

    public function show(SupportTicket $ticket): JsonResponse
    {
        $ticket->load(['user:id,name,email,phone', 'messages.user:id,name', 'assignee:id,name']);

        return response()->json([
            'data' => $this->transformTicket($ticket),
        ]);
    }

    public function update(Request $request, SupportTicket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(SupportTicket::statuses())],
            'priority' => ['required', Rule::in(SupportTicket::priorities())],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'category' => ['nullable', 'string', 'max:120'],
        ]);

        $ticket->fill($validated);
        if ($validated['status'] === SupportTicket::STATUS_CLOSED) {
            $ticket->closed_at = now();
        } elseif ($ticket->closed_at) {
            $ticket->closed_at = null;
        }
        $ticket->save();

        $this->auditService->log(
            userId: $ticket->user_id,
            actorId: $request->user()?->id,
            action: 'admin.support_ticket_updated',
            subject: $ticket,
            request: $request,
            meta: $validated
        );

        return response()->json([
            'data' => $this->transformTicket($ticket->fresh(['user:id,name,email,phone', 'messages.user:id,name', 'assignee:id,name'])),
        ]);
    }

    public function reply(Request $request, SupportTicket $ticket): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $actor = $request->user();

        $message = $ticket->messages()->create([
            'user_id' => $actor?->id,
            'sender_type' => 'support',
            'message' => $validated['message'],
        ]);

        $ticket->status = SupportTicket::STATUS_RESPONDED;
        $ticket->assigned_to = $ticket->assigned_to ?: $actor?->id;
        $ticket->last_message_at = $message->created_at;
        $ticket->first_responded_at = $ticket->first_responded_at ?: $message->created_at;
        $ticket->save();

        $this->auditService->log(
            userId: $ticket->user_id,
            actorId: $actor?->id,
            action: 'admin.support_ticket_replied',
            subject: $ticket,
            request: $request,
            meta: ['message_id' => $message->id]
        );

        return response()->json([
            'data' => $this->transformTicket($ticket->fresh(['user:id,name,email,phone', 'messages.user:id,name', 'assignee:id,name'])),
        ]);
    }

    protected function transformTicketSummary(SupportTicket $ticket): array
    {
        $lastMessage = $ticket->messages->first();

        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'status_label' => $ticket->status_label,
            'priority' => $ticket->priority,
            'priority_label' => $ticket->priority_label,
            'category' => $ticket->category,
            'user' => [
                'id' => $ticket->user?->id,
                'name' => $ticket->user?->name,
                'email' => $ticket->user?->email,
            ],
            'assignee' => $ticket->assignee ? [
                'id' => $ticket->assignee->id,
                'name' => $ticket->assignee->name,
            ] : null,
            'last_message_preview' => $lastMessage ? mb_strimwidth((string) $lastMessage->message, 0, 120, '...') : null,
            'last_message_at' => optional($ticket->last_message_at)->toIso8601String(),
        ];
    }

    protected function transformTicket(SupportTicket $ticket): array
    {
        return array_merge($this->transformTicketSummary($ticket), [
            'source' => $ticket->source,
            'created_at' => optional($ticket->created_at)->toIso8601String(),
            'first_responded_at' => optional($ticket->first_responded_at)->toIso8601String(),
            'closed_at' => optional($ticket->closed_at)->toIso8601String(),
            'user' => [
                'id' => $ticket->user?->id,
                'name' => $ticket->user?->name,
                'email' => $ticket->user?->email,
                'phone' => $ticket->user?->phone,
            ],
            'messages' => $ticket->messages->map(fn (SupportTicketMessage $message) => [
                'id' => $message->id,
                'sender_type' => $message->sender_type,
                'sender_name' => $message->user?->name,
                'body' => $message->message,
                'created_at' => optional($message->created_at)->toIso8601String(),
            ])->values()->all(),
        ]);
    }
}
