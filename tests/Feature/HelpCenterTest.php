<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * The help section: what it answers and what it says when it refuses.
 */
class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withHeader('Accept-Language', 'ru');
    }

    /**
     * The questions used to sit in the config and reach no one: the page
     * rendered a contact form and nothing else.
     */
    public function test_the_overview_carries_the_questions_the_page_shows(): void
    {
        $this->master();

        $response = $this->getJson('/api/v1/help/overview')->assertOk();

        $faq = $response->json('data.faq');

        $this->assertNotEmpty($faq);
        $this->assertSame('Клиент не получил напоминание — что проверить?', $faq[0]['question']);

        // Every answer points at a screen of this CRM. The previous set sent
        // the master to a Google Calendar sync, «Настройки → Команда» and
        // «Настройки → Безопасность → Экспорт данных», none of which exist.
        foreach ($faq as $item) {
            $this->assertNotNull($item['link']);
            $this->assertStringStartsWith('/', $item['link']['url']);
        }
    }

    public function test_the_overview_states_the_rules_the_form_has_to_obey(): void
    {
        $this->master();

        $this->getJson('/api/v1/help/overview')
            ->assertOk()
            ->assertJsonPath('data.limits.message_min', config('help.limits.message_min'))
            ->assertJsonPath('data.limits.reply_min', config('help.limits.reply_min'))
            ->assertJsonPath('data.attachment.max_mb', config('help.attachment.max_mb'))
            ->assertJsonPath('data.support.contact_email', config('help.support.contact_email'));
    }

    /**
     * A ten-character minimum nobody announced used to be answered with
     * «Предоставленные данные недействительны».
     */
    public function test_a_short_message_is_refused_by_name(): void
    {
        $this->master();

        $response = $this->postJson('/api/v1/help/tickets', [
            'subject' => 'Напоминания',
            'message' => 'Помогите',
        ])->assertStatus(422);

        $this->assertSame(
            'Поле Сообщение должно содержать не менее 10 символов.',
            $response->json('error.fields')['message'][0]
        );
    }

    public function test_a_ticket_is_created_with_its_first_message(): void
    {
        $user = $this->master();

        $this->postJson('/api/v1/help/tickets', [
            'subject' => 'Проблема с напоминаниями',
            'message' => 'Клиенты не получают напоминания уже второй день.',
        ])->assertCreated()
            ->assertJsonPath('data.subject', 'Проблема с напоминаниями');

        $ticket = SupportTicket::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(1, $ticket->messages()->count());
    }

    public function test_a_short_reply_is_refused_by_name_too(): void
    {
        $user = $this->master();

        $this->postJson('/api/v1/help/tickets', [
            'subject' => 'Проблема с напоминаниями',
            'message' => 'Клиенты не получают напоминания уже второй день.',
        ])->assertCreated();

        $ticket = SupportTicket::where('user_id', $user->id)->firstOrFail();

        $response = $this->postJson("/api/v1/help/tickets/{$ticket->id}/messages", [
            'message' => 'ок',
        ])->assertStatus(422);

        $this->assertSame(
            'Поле Сообщение должно содержать не менее 3 символов.',
            $response->json('error.fields')['message'][0]
        );
    }

    public function test_someone_elses_ticket_stays_theirs(): void
    {
        $owner = User::factory()->create();
        $ticket = SupportTicket::create([
            'user_id' => $owner->id,
            'subject' => 'Чужое обращение',
            'status' => SupportTicket::STATUS_WAITING,
            'last_message_at' => now(),
        ]);

        $this->master();

        $this->getJson("/api/v1/help/tickets/{$ticket->id}")->assertForbidden();
    }

    private function master(): User
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        return $user;
    }
}
