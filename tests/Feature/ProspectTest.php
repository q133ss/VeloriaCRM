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
