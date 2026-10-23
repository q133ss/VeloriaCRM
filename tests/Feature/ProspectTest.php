<?php

namespace Tests\Feature;

use App\Models\Landing;
use App\Models\Prospect;
use App\Models\Service;
use App\Models\User;
use App\Services\Prospects\ProspectBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProspectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('landing_media');
        // No provider answers in tests: the parser falls back to the group's own items.
        config(['ai.local.enabled' => false, 'services.openai.key' => null, 'services.openai.api_key' => null]);

        $image = UploadedFile::fake()->image('work.jpg', 900, 700);
        $jpeg = $image->get();
        Http::fake([
            'sun9-1.userapi.com/*' => Http::response($jpeg, 200, ['Content-Type' => 'image/jpeg']),
            '*' => Http::response('nope', 404),
        ]);
    }

    private function admin(string $role = User::ADMIN_ROLE_SUPER_ADMIN): User
    {
        return User::factory()->create(['is_admin' => true, 'admin_role' => $role, 'status' => User::STATUS_ACTIVE]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'external_id' => 'nails_anna',
            'url' => 'https://vk.com/nails_anna',
            'title' => 'Маникюр у Анны | Казань',
            'description' => "Маникюр и педикюр в центре Казани.\nСтерильно, аккуратно.\n\nПрайс в товарах",
            'address' => 'Казань, ул. Баумана, 1',
            'phone' => '8 (900) 123-45-67',
            'booking' => ['service' => 'YCLIENTS', 'url' => 'https://n123.yclients.com'],
            'services' => [
                ['name' => 'Маникюр с покрытием', 'price' => '1 800 ₽'],
                ['name' => 'Педикюр', 'price' => 2200, 'duration' => 90, 'category' => 'Педикюр'],
            ],
            'avatar' => 'https://sun9-1.userapi.com/avatar.jpg',
            'photos' => [
                'https://sun9-1.userapi.com/1.jpg',
                'https://evil.example.com/2.jpg',
                'https://sun9-1.userapi.com/3.jpg',
            ],
        ], $overrides);
    }

    public function test_admin_builds_an_account_with_catalog_schedule_and_page(): void
    {
        Sanctum::actingAs($this->admin());

        $response = $this->postJson('/api/v1/admin/prospects', $this->payload())->assertCreated();

        $prospect = Prospect::firstOrFail();
        $user = $prospect->user;

        $this->assertStringEndsWith('@' . Prospect::EMAIL_DOMAIN, $user->email);
        $this->assertSame('+79001234567', $user->phone);
        $this->assertSame('YCLIENTS', $prospect->booking_service);
        $this->assertSame('items', $prospect->services_source);
        $this->assertSame(2, Service::where('user_id', $user->id)->count());
        $this->assertEquals(1800, Service::where('user_id', $user->id)->where('name', 'Маникюр с покрытием')->value('base_price'));
        $this->assertNotEmpty($user->setting->schedule_rules);

        $landing = Landing::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('nails-anna', $landing->slug);
        $this->assertNotEmpty(data_get($landing->settings, 'images'));
        // The disallowed host was skipped, nothing was fetched from it.
        Http::assertNotSent(fn ($request) => str_contains($request->url(), 'evil.example.com'));

        $response->assertJsonPath('data.login', $user->email)
            ->assertJsonPath('data.landing_url', route('landings.public', 'nails-anna'));
        $this->assertNotNull($response->json('data.password'));
        $this->assertNotNull($response->json('data.claim_url'));

        $this->get('/l/nails-anna')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_without_a_model_items_are_grouped_and_non_services_dropped(): void
    {
        $result = app(\App\Services\Prospects\ProspectServiceParser::class)->parse([
            ['name' => 'Smart педикюр', 'price' => '2 975 ₽'],
            ['name' => 'Маникюр без г-лака', 'price' => '1 500 ₽'],
            ['name' => 'Маникюр «Все включено» Акция', 'price' => '2 099 ₽'],
            ['name' => 'Маникюр + френч АКЦИЯ', 'price' => '2 399 ₽'],
            ['name' => 'Брови комплекс', 'price' => '1 500 ₽'],
            ['name' => 'Гарантия', 'price' => 'бесплатно'],
        ], [], 'nails');

        $byName = collect($result['services'])->keyBy('name');

        $this->assertSame('items', $result['source']);
        $this->assertFalse($byName->has('Гарантия'));
        $this->assertSame(['Педикюр', 90], [$byName['Smart педикюр']['category'], $byName['Smart педикюр']['duration']]);
        $this->assertSame(['Маникюр', 60], [$byName['Маникюр без г-лака']['category'], $byName['Маникюр без г-лака']['duration']]);
        $this->assertTrue($byName->has('Маникюр «Все включено»'));
        $this->assertTrue($byName->has('Маникюр + френч'));
        $this->assertSame('Брови', $byName['Брови комплекс']['category']);
    }

    public function test_the_page_skips_vk_contact_lines_and_cleans_the_title(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/v1/admin/prospects', $this->payload([
            'title' => 'Маникюр| Марьино Москва | Beauty Zone Bo 24/7',
            'description' => "Для записи на маникюр по Акции пишите\nvk.me/nailboo\n\n✅ Марьино, МСК, Луговой проезд, 9к2\n\n✅Качественный маникюр и педикюр от мастеров с опытом от 5 лет\n\n🔥СКИДКИ для новых клиентов!",
        ]))->assertCreated();

        $this->get('/l/nailboo-anna')->assertNotFound();
        $html = $this->get('/l/nails-anna')->assertOk()->getContent();

        $this->assertStringContainsString('Маникюр · Марьино Москва · Beauty Zone Bo 24/7', $html);
        $this->assertStringNotContainsString('Маникюр|', $html);
        $this->assertStringContainsString('Качественный маникюр и педикюр от мастеров с опытом от 5 лет', $html);
        $this->assertStringNotContainsString('по Акции пишите', $html);
    }

    public function test_outreach_pages_use_the_price_list_layout_with_services_by_category(): void
    {
        Sanctum::actingAs($this->admin());
        $this->postJson('/api/v1/admin/prospects', $this->payload())->assertCreated();

        $landing = Prospect::firstOrFail()->landing;
        $this->assertSame('landings.full.price-list', $landing->landing);

        $html = $this->get('/l/' . $landing->slug)->assertOk()->getContent();
        // Groups from the catalog, real prices, the formatted phone, and no stock placeholder for visitors.
        $this->assertStringContainsString('pl-group-title', $html);
        $this->assertStringContainsString('1 800 ₽', $html);
        $this->assertStringContainsString('+7 900 123-45-67', $html);
        $this->assertStringNotContainsString('placeholder.svg', $html);
    }

    public function test_without_any_prices_the_niche_defaults_are_used(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/v1/admin/prospects', [
            'external_id' => 'club12345',
            'title' => 'Барбершоп Борода',
            'description' => 'Мужские стрижки',
        ])->assertCreated();

        $prospect = Prospect::firstOrFail();
        $this->assertSame('default', $prospect->services_source);
        $this->assertGreaterThan(0, Service::where('user_id', $prospect->user_id)->count());
        // club123 says nothing; the slug comes from the name.
        $this->assertSame('barbersop-boroda', $prospect->landing->slug);
    }

    public function test_building_the_same_group_again_rebuilds_in_place(): void
    {
        Sanctum::actingAs($this->admin());

        $first = $this->postJson('/api/v1/admin/prospects', $this->payload())->json('data');
        $this->postJson('/api/v1/admin/prospects', $this->payload([
            'services' => [['name' => 'Только маникюр', 'price' => 1500]],
        ]))->assertCreated();

        $this->assertSame(1, Prospect::count());
        $prospect = Prospect::first();
        $this->assertSame($first['login'], $prospect->user->email);
        $this->assertSame(['Только маникюр'], Service::where('user_id', $prospect->user_id)->pluck('name')->all());
        $this->assertSame(1, Landing::where('user_id', $prospect->user_id)->count());
    }

    public function test_only_the_super_admin_builds_prospects(): void
    {
        Sanctum::actingAs($this->admin(User::ADMIN_ROLE_SUPPORT));

        $this->postJson('/api/v1/admin/prospects', $this->payload())->assertForbidden();
    }

    public function test_scraper_token_opens_prospects_and_nothing_else(): void
    {
        $admin = $this->admin();
        Sanctum::actingAs($admin);
        $token = $this->postJson('/api/v1/admin/prospects/token')->assertOk()->json('data.token');

        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/admin/prospects')->assertOk();
        $this->withToken($token)->getJson('/api/v1/admin/users')->assertForbidden();
        $this->withToken($token)->getJson('/api/v1/admin/prospects/vk/nobody')->assertNotFound();
    }

    public function test_master_claims_the_account_with_her_own_email_and_password(): void
    {
        Sanctum::actingAs($this->admin());
        $claimUrl = $this->postJson('/api/v1/admin/prospects', $this->payload())->json('data.claim_url');
        $this->app['auth']->forgetGuards();

        $path = parse_url($claimUrl, PHP_URL_PATH);
        $this->get($path)->assertOk()->assertSee('Это ваш кабинет');

        $this->post($path, [
            'email' => 'anna@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ])->assertRedirect('/dashboard')->assertCookie('token');

        $prospect = Prospect::first();
        $this->assertSame(Prospect::STATUS_CLAIMED, $prospect->status);
        $this->assertNull($prospect->claim_token);
        $this->assertNull($prospect->password);
        $this->assertSame('anna@example.com', $prospect->user->email);

        $this->get($path)->assertNotFound();
        $this->get('/l/nails-anna')->assertOk()->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_opens_of_the_claim_link_and_the_page_are_counted_apart_from_bots(): void
    {
        Sanctum::actingAs($this->admin());
        $data = $this->postJson('/api/v1/admin/prospects', $this->payload())->json('data');
        $claimPath = parse_url($data['claim_url'], PHP_URL_PATH);
        $landingPath = parse_url($data['landing_url'], PHP_URL_PATH);

        $person = ['User-Agent' => 'Mozilla/5.0 (iPhone) Safari', 'Referer' => 'https://vk.com/im'];

        // A guest (not the admin session) opens both links, and VK's preview robot opens one.
        $this->app['auth']->forgetGuards();
        $this->withHeaders($person)->get($claimPath)->assertOk();
        $this->withHeaders($person)->get($claimPath)->assertOk();
        $this->withHeaders($person)->get($landingPath)->assertOk();
        $this->withHeaders(['User-Agent' => 'vkShare; +http://vk.com/dev/Share'])->get($claimPath)->assertOk();

        Sanctum::actingAs($this->admin());
        $row = collect($this->getJson('/api/v1/admin/prospects')->json('data'))->firstWhere('id', $data['id']);

        $this->assertSame(2, $row['visits']['claim']['views']);
        $this->assertSame(1, $row['visits']['claim']['visitors']);
        $this->assertSame(1, $row['visits']['landing']['views']);
        $this->assertSame(1, $row['visits']['bot_hits']);
        $this->assertCount(1, $this->getJson('/api/v1/admin/prospects?status=opened')->json('data'));

        $log = $this->getJson('/api/v1/admin/prospects/' . $data['id'] . '/visits')->json('data');
        $this->assertCount(4, $log);
        $this->assertSame('vk.com/im', preg_replace('#^https?://#', '', $log[array_search('landing', array_column($log, 'kind'))]['referer']));
    }

    public function test_claimed_prospect_shows_the_email_she_registered_with(): void
    {
        Sanctum::actingAs($this->admin());
        $data = $this->postJson('/api/v1/admin/prospects', $this->payload())->json('data');
        $this->app['auth']->forgetGuards();

        $this->post(parse_url($data['claim_url'], PHP_URL_PATH), [
            'email' => 'anna@example.com', 'password' => 'secret-pass-1', 'password_confirmation' => 'secret-pass-1',
        ]);

        Sanctum::actingAs($this->admin());
        $row = collect($this->getJson('/api/v1/admin/prospects')->json('data'))->firstWhere('id', $data['id']);

        $this->assertSame('claimed', $row['status']);
        $this->assertSame('anna@example.com', $row['email']);
    }

    public function test_claim_refuses_an_email_that_already_has_an_account(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        Sanctum::actingAs($this->admin());
        $path = parse_url($this->postJson('/api/v1/admin/prospects', $this->payload())->json('data.claim_url'), PHP_URL_PATH);
        $this->app['auth']->forgetGuards();

        $this->post($path, [
            'email' => 'taken@example.com',
            'password' => 'secret-pass',
            'password_confirmation' => 'secret-pass',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Prospect::first()->isPending());
    }

    public function test_unclaimed_accounts_expire(): void
    {
        $prospect = app(ProspectBuilder::class)->build($this->payload() + ['channel' => 'vk']);
        $userId = $prospect->user_id;
        $prospect->forceFill(['created_at' => now()->subDays(40)])->save();

        $this->artisan('prospects:expire')->assertSuccessful();

        $this->assertNull(User::find($userId));
        $this->assertSame(0, Landing::count());
        $this->assertSame(Prospect::STATUS_EXPIRED, $prospect->fresh()->status);
    }

    public function test_a_zip_with_prospect_json_and_photos_builds_the_same_account(): void
    {
        Sanctum::actingAs($this->admin());

        $zipPath = tempnam(sys_get_temp_dir(), 'pz') . '.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('club777/prospect.json', json_encode($this->payload([
            'external_id' => 'brows_kate',
            'title' => 'Брови Катя',
            'avatar' => 'images/avatar.jpg',
            'photos' => ['images/1.jpg', '../outside.jpg'],
        ])));
        $photo = UploadedFile::fake()->image('p.jpg', 600, 600);
        $zip->addFromString('club777/images/avatar.jpg', $photo->get());
        $zip->addFromString('club777/images/1.jpg', $photo->get());
        $zip->close();

        $this->post('/api/v1/admin/prospects/import', [
            'file' => new UploadedFile($zipPath, 'brows.zip', 'application/zip', null, true),
        ], ['Accept' => 'application/json'])->assertCreated();

        $landing = Prospect::where('external_id', 'brows_kate')->firstOrFail()->landing;
        $this->assertNotEmpty(data_get($landing->settings, 'images'));
        Http::assertNothingSent();
    }

    public function test_pending_prospects_stay_out_of_the_admin_overview(): void
    {
        Sanctum::actingAs($admin = $this->admin());
        $this->postJson('/api/v1/admin/prospects', $this->payload())->assertCreated();

        $this->getJson('/api/v1/admin/overview')->assertJsonPath('summary.total_users', 1);
    }
}
