<?php

namespace Tests\Feature;

use App\Models\AccountIdentityLink;
use App\Models\AdminUser;
use App\Models\Community;
use App\Models\SupportTicket;
use App\Models\User;
use App\Notifications\AdminResetPassword;
use App\Notifications\MarketplaceActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdminIdentityTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_sessions_keep_both_browser_logins_independent(): void
    {
        config(['session.driver' => 'database']);
        $admin = AdminUser::factory()->create(['password' => 'AdminSeguro1234']);
        $client = User::factory()->create(['password' => 'ClienteSeguro1234']);
        $cookies = [];
        $visit = function (string $method, string $url, array $data = []) use (&$cookies) {
            Auth::forgetGuards();
            $this->app->forgetInstance('session.store');
            $this->app['session']->forgetDrivers();
            $this->defaultCookies = $cookies;
            $response = $method === 'GET' ? $this->get($url) : $this->post($url, $data);
            $cookieName = config('session.cookie');
            if ($cookie = $response->getCookie($cookieName)) {
                $cookies[$cookieName] = $cookie->getValue();
            }

            return $response;
        };
        $visit('POST', route('login'), ['email' => $client->email, 'password' => 'ClienteSeguro1234'])->assertRedirect();
        $visit('GET', route('profile.edit'))->assertOk()->assertViewHas('user', fn ($user) => $user->is($client));
        $visit('POST', route('admin.login.store'), ['email' => $admin->email, 'password' => 'AdminSeguro1234'])->assertRedirect();
        $visit('GET', route('admin.index'))->assertOk();
        $visit('GET', route('profile.edit'))->assertOk()->assertViewHas('user', fn ($user) => $user->is($client));
        $visit('POST', route('admin.logout'))->assertRedirect(route('admin.login'));
        $visit('GET', route('admin.index'))->assertRedirect(route('admin.login'));
        $visit('GET', route('profile.edit'))->assertOk()->assertViewHas('user', fn ($user) => $user->is($client));
        $this->assertDatabaseHas('sessions', ['user_id' => $client->id]);
    }

    public function test_team_and_identity_pages_render_without_credentials(): void
    {
        $owner = AdminUser::factory()->superadmin()->create(['name' => 'Cuenta propietaria', 'email' => 'propietario@example.test']);
        AdminUser::factory()->create(['name' => 'Andrea Martínez', 'email' => 'andrea@example.test', 'phone' => '2220000011', 'phone_verified_at' => null]);
        AdminUser::factory()->create(['name' => 'Carlos García', 'email' => 'carlos@example.test', 'phone' => '2220000022', 'active' => false]);
        $team = $this->actingAs($owner, 'admin')->get(route('admin.team.index'))->assertOk()->assertSee('Invitar administrador')->assertDontSee($owner->password);
        $this->get(route('admin.identity.show'))->assertOk()->assertSee('Crear mi cuenta de Plaza Local');
        if (getenv('PLAZA_UI_REVIEW') === '1') {
            $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
            $css = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
            $html = preg_replace('/<script\b[^>]*>.*?<\/script>/s', '', $team->getContent());
            $html = preg_replace('/<link\b[^>]*>/s', '', $html);
            $html = str_replace('</head>', '<style>'.$css.'</style></head>', $html);
            File::ensureDirectoryExists(storage_path('app/ui-review'));
            file_put_contents(storage_path('app/ui-review/admin-team.html'), $html);
        }
    }

    public function test_expired_or_exhausted_sms_challenge_cannot_mark_phone_verified(): void
    {
        Http::fake();
        $user = User::factory()->create(['phone_verified_at' => null]);
        foreach ([['expires' => now()->subMinute()->timestamp, 'attempts' => 0], ['expires' => now()->addMinute()->timestamp, 'attempts' => 5]] as $limits) {
            $this->actingAs($user, 'web')->withSession(['user.phone_challenge' => $limits + ['sid' => 'VEtest', 'identity_id' => $user->id, 'phone' => $user->phone]])
                ->post(route('phone.verify'), ['code' => '1234'])->assertStatus(422);
        }
        Http::assertNothingSent();
        $this->assertNull($user->fresh()->phone_verified_at);
    }

    public function test_admin_preview_keeps_read_only_navigation_and_notifications_use_admin_support(): void
    {
        $admin = AdminUser::factory()->create();
        $client = User::factory()->create();
        $ticket = SupportTicket::create(['user_id' => $client->id, 'category' => 'general', 'subject' => 'Ayuda', 'status' => 'open']);
        $admin->notify(new MarketplaceActivity('Soporte', 'Nuevo caso', 'support.show', ['ticket' => $ticket->id], 'support'));
        $this->actingAs($admin, 'admin')->get(route('admin.users.preview', $client))->assertOk()
            ->assertSee(route('admin.users.preview', [$client, 'tab' => 'reviews']), false)->assertDontSee('>Seguir</button>', false);
        $this->patch(route('admin.notifications.open', $admin->notifications()->firstOrFail()))->assertRedirect(route('admin.support.show', $ticket));
        $this->get(route('admin.support.show', $ticket))->assertOk()->assertSee(route('admin.support.reply', $ticket), false);
    }

    public function test_admin_login_does_not_authenticate_marketplace_and_logout_preserves_client(): void
    {
        $client = User::factory()->create();
        $admin = AdminUser::factory()->create(['password' => 'AdminSeguro1234']);
        $this->actingAs($client, 'web')->post(route('admin.login.store'), ['email' => $admin->email, 'password' => 'AdminSeguro1234'])
            ->assertRedirect(route('admin.verification.notice'));
        $this->assertAuthenticatedAs($admin, 'admin');
        $this->assertAuthenticatedAs($client, 'web');
        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest('admin');
        $this->assertAuthenticatedAs($client, 'web');
    }

    public function test_cookie_names_and_paths_are_distinct(): void
    {
        $this->get(route('login'));
        $webCookie = config('session.cookie');
        $response = $this->get(route('admin.login'));
        $this->assertSame($webCookie.'_admin', config('session.cookie'));
        $cookies = collect($response->headers->getCookies());
        $cookie = $cookies->first(fn ($cookie) => $cookie->getName() === $webCookie.'_admin');
        $this->assertNotNull($cookie);
        $this->assertSame('/administracion', $cookie->getPath());
        $this->get(route('login'));
        $this->assertSame($webCookie, config('session.cookie'));
        $this->assertSame('/', config('session.path'));
    }

    public function test_marketplace_credentials_cannot_log_in_to_administration(): void
    {
        $client = User::factory()->create(['password' => 'ClienteSeguro123']);
        $this->post(route('admin.login.store'), ['email' => $client->email, 'password' => 'ClienteSeguro123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    }

    public function test_administration_requires_both_verifications_and_active_access(): void
    {
        $admin = AdminUser::factory()->create(['phone_verified_at' => null]);
        $this->actingAs($admin, 'admin')->get(route('admin.index'))->assertRedirect(route('admin.verification.notice'));
        $admin->forceFill(['phone_verified_at' => now(), 'email_verified_at' => null])->save();
        $this->get(route('admin.index'))->assertRedirect(route('admin.verification.notice'));
        $admin->forceFill(['email_verified_at' => now(), 'active' => false])->save();
        $this->get(route('admin.index'))->assertForbidden();
    }

    public function test_email_verification_cannot_verify_another_administrative_identity(): void
    {
        $first = AdminUser::factory()->create(['email_verified_at' => null]);
        $other = AdminUser::factory()->create(['email_verified_at' => null]);
        $url = URL::temporarySignedRoute('admin.verification.verify', now()->addMinutes(10), ['id' => $other->id, 'hash' => sha1($other->email)]);
        $this->actingAs($first, 'admin')->get($url)->assertForbidden();
        $this->assertNull($other->fresh()->email_verified_at);
        $ownUrl = URL::temporarySignedRoute('admin.verification.verify', now()->addMinutes(10), ['id' => $first->id, 'hash' => sha1($first->email)]);
        $this->get($ownUrl)->assertRedirect();
        $this->assertNotNull($first->fresh()->email_verified_at);
    }

    public function test_user_first_invitation_links_exact_contacts_without_changing_client_roles(): void
    {
        $owner = AdminUser::factory()->superadmin()->create();
        $client = User::factory()->create(['password' => 'ClienteSeguro123']);
        $response = $this->actingAs($owner, 'admin')->post(route('admin.team.invite'), ['name' => $client->name, 'email' => $client->email, 'phone' => $client->phone]);
        $response->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('invitation_url');
        $url = session('invitation_url');
        $this->post($url, ['password' => 'AdministrativoSeguro789', 'password_confirmation' => 'AdministrativoSeguro789', 'role' => 'superadmin'])
            ->assertRedirect(route('admin.verification.notice'))->assertSessionHasNoErrors();
        $admin = AdminUser::where('email', $client->email)->firstOrFail();
        $this->assertTrue($admin->hasRole('admin'));
        $this->assertFalse($client->fresh()->hasRole('admin'));
        $this->assertNull($admin->phone_verified_at);
        $this->assertDatabaseHas('account_identity_links', ['admin_user_id' => $admin->id, 'user_id' => $client->id, 'approved_by_admin_id' => $owner->id]);
        $this->post($url, ['password' => 'AdministrativoSeguro789', 'password_confirmation' => 'AdministrativoSeguro789'])->assertStatus(422);
    }

    public function test_invitation_rejects_mismatched_contact_pair_and_expired_token(): void
    {
        $owner = AdminUser::factory()->superadmin()->create();
        $client = User::factory()->create();
        $this->actingAs($owner, 'admin')->post(route('admin.team.invite'), ['name' => $client->name, 'email' => $client->email, 'phone' => '2220000099'])->assertStatus(422);
        $this->post(route('admin.team.invite'), ['name' => $client->name, 'email' => $client->email, 'phone' => $client->phone])->assertRedirect();
        $url = session('invitation_url');
        DB::table('admin_invitations')->update(['expires_at' => now()->subMinute()]);
        $this->post($url, ['password' => 'AdministrativoSeguro789', 'password_confirmation' => 'AdministrativoSeguro789'])->assertStatus(422);
        $this->assertDatabaseCount('admin_users', 1);
    }

    public function test_verified_admin_can_create_only_one_matching_marketplace_identity(): void
    {
        $admin = AdminUser::factory()->create();
        $payload = ['community_id' => Community::firstOrFail()->id, 'password' => 'ComercialSeguro456', 'password_confirmation' => 'ComercialSeguro456', 'email' => 'ignored@example.test', 'phone' => '2220000088'];
        $this->actingAs($admin, 'admin')->post(route('admin.identity.create'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $client = User::where('email', $admin->email)->firstOrFail();
        $this->assertSame($admin->phone, $client->phone);
        $this->assertNotNull($client->phone_verified_at);
        $this->assertTrue(Hash::check('ComercialSeguro456', $client->password));
        $this->assertFalse($client->hasRole('admin'));
        $this->post(route('admin.identity.create'), $payload)->assertStatus(422);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_no_provider_means_no_sms_and_no_fake_verification(): void
    {
        Http::fake();
        config(['phone_verification.account_sid' => '', 'phone_verification.auth_token' => '', 'phone_verification.service_sid' => '']);
        $user = User::factory()->create(['phone_verified_at' => null]);
        $this->actingAs($user, 'web')->post(route('phone.send'), ['phone' => $user->phone])->assertSessionHasErrors('phone');
        $this->post(route('phone.verify'), ['code' => '1234'])->assertStatus(422);
        $this->assertNull($user->fresh()->phone_verified_at);
        Http::assertNothingSent();
    }

    public function test_sms_code_requires_exactly_four_digits_for_both_identities(): void
    {
        Http::fake();
        foreach ([['web', User::factory()->create(['phone_verified_at' => null]), 'phone.verify'], ['admin', AdminUser::factory()->create(['phone_verified_at' => null]), 'admin.phone.verify']] as [$guard, $identity, $route]) {
            $this->actingAs($identity, $guard);
            foreach (['123', '123456', '12ab'] as $code) {
                $this->post(route($route), ['code' => $code])->assertSessionHasErrors('code');
            }
            $this->assertNull($identity->fresh()->phone_verified_at);
        }
        Http::assertNothingSent();
    }

    public function test_real_adapter_uses_provider_challenge_and_marks_only_approved_number(): void
    {
        config(['phone_verification.account_sid' => 'ACtest', 'phone_verification.auth_token' => 'test-only', 'phone_verification.service_sid' => 'VAtest']);
        Http::preventStrayRequests();
        Http::fake([
            '*/Verifications' => Http::response(['status' => 'pending', 'sid' => 'VEtest']),
            '*/VerificationCheck' => Http::sequence()->push(['status' => 'pending'])->push(['status' => 'approved']),
        ]);
        $admin = AdminUser::factory()->create(['phone_verified_at' => null]);
        $this->actingAs($admin, 'admin')->post(route('admin.phone.send'), ['phone' => $admin->phone])->assertRedirect()->assertSessionHasNoErrors();
        Http::assertSent(fn ($request) => $request->url() === 'https://verify.twilio.com/v2/Services/VAtest/Verifications' && $request['To'] === '+52'.$admin->phone && $request['Channel'] === 'sms');
        $this->post(route('admin.phone.verify'), ['code' => '0000'])->assertSessionHasErrors('code');
        $this->assertNull($admin->fresh()->phone_verified_at);
        $this->post(route('admin.phone.verify'), ['code' => '0123'])->assertRedirect(route('admin.verification.notice'));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/VerificationCheck') && $request['Code'] === '0123');
        $this->assertNotNull($admin->fresh()->phone_verified_at);
        $this->post(route('admin.phone.verify'), ['code' => '0123'])->assertStatus(422);
    }

    public function test_phone_cannot_be_shared_without_an_authorized_link(): void
    {
        Http::fake();
        $admin = AdminUser::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($user, 'web')->post(route('phone.send'), ['phone' => $admin->phone])->assertSessionHasErrors('phone');
        $this->put(route('profile.update'), ['name' => $user->name, 'phone' => $admin->phone, 'community_id' => Community::firstOrFail()->id])->assertSessionHasErrors('phone');
        $this->assertNotSame($admin->phone, $user->fresh()->phone);
        Http::assertNothingSent();
    }

    public function test_admin_and_marketplace_password_recovery_tokens_are_not_interchangeable(): void
    {
        Notification::fake();
        $admin = AdminUser::factory()->create(['password' => 'AdminAnterior123']);
        $user = User::factory()->create(['email' => $admin->email, 'phone' => $admin->phone, 'password' => 'ClienteAnterior456']);
        AccountIdentityLink::create(['admin_user_id' => $admin->id, 'user_id' => $user->id, 'email' => $user->email, 'phone' => $user->phone, 'approved_at' => now()]);
        $this->post(route('admin.password.email'), ['email' => $admin->email])->assertRedirect();
        Notification::assertSentTo($admin, AdminResetPassword::class);
        Notification::assertNotSentTo($user, AdminResetPassword::class);
        $clientToken = Password::broker('users')->createToken($user);
        $payload = ['email' => $admin->email, 'token' => $clientToken, 'password' => 'NuevoAdminSeguro123', 'password_confirmation' => 'NuevoAdminSeguro123'];
        $this->post(route('admin.password.update'), $payload)->assertSessionHasErrors('email');
        $payload['token'] = Password::broker('admins')->createToken($admin);
        $this->post(route('admin.password.update'), $payload)->assertRedirect(route('admin.login'));
        $this->assertTrue(Hash::check('NuevoAdminSeguro123', $admin->fresh()->password));
        $this->assertTrue(Hash::check('ClienteAnterior456', $user->fresh()->password));
    }
}
