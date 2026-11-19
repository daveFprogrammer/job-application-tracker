<?php

namespace Tests\Feature;

use App\Actions\ResetDemoAccount;
use App\Jobs\SendReminder;
use App\Models\JobApplication;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DemoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['demo.enabled' => true]);
    }

    private function demoUser(): User
    {
        return User::query()->where('is_demo', true)->sole();
    }

    public function test_the_demo_login_is_not_available_when_disabled(): void
    {
        config(['demo.enabled' => false]);

        $this->post(route('demo.login'))->assertNotFound();
        $this->assertGuest();
    }

    public function test_visitors_can_sign_into_a_ready_made_demo_board(): void
    {
        $this->post(route('demo.login'))->assertRedirect(route('dashboard'));

        $user = $this->demoUser();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame(11, $user->jobApplications()->count());
        $this->assertTrue($user->jobApplications()->whereHas('interviews', fn ($query) => $query->where('scheduled_at', '>', now()))->exists());

        $this->get(route('dashboard'))->assertOk();
        $this->get(route('applications.index'))->assertOk();
    }

    public function test_every_visitor_shares_the_same_demo_account(): void
    {
        $this->post(route('demo.login'));
        $this->post(route('logout'));
        $this->post(route('demo.login'));

        $this->assertSame(1, User::query()->where('is_demo', true)->count());
    }

    public function test_signed_in_users_are_not_switched_to_the_demo(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('demo.login'))->assertRedirect();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, User::query()->where('is_demo', true)->count());
    }

    public function test_registering_with_the_demo_address_does_not_create_a_demo_user(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Intruder',
            'email' => config('demo.email'),
            'password' => 'password-1234',
            'password_confirmation' => 'password-1234',
        ]);

        $this->assertFalse(User::query()->where('email', config('demo.email'))->sole()->is_demo);

        // The demo still works, with an address of its own.
        $this->post(route('logout'));
        $this->post(route('demo.login'))->assertRedirect(route('dashboard'));
        $this->assertNotSame(config('demo.email'), $this->demoUser()->email);
        $this->assertAuthenticatedAs($this->demoUser());
    }

    public function test_the_demo_board_can_be_edited(): void
    {
        $this->post(route('demo.login'));

        $this->post(route('applications.store'), [
            'company' => 'Acme',
            'position' => 'Developer',
            'status' => 'wishlist',
        ])->assertRedirect();

        $this->assertSame(12, $this->demoUser()->jobApplications()->count());
    }

    public function test_the_demo_profile_and_credentials_are_read_only(): void
    {
        $this->post(route('demo.login'));
        $user = $this->demoUser();

        $this->from(route('profile.edit'))
            ->patch(route('profile.update'), ['name' => 'Hacker', 'email' => 'hacker@example.com'], ['X-Inertia' => 'true'])
            ->assertRedirect(route('profile.edit'));

        $this->delete(route('profile.destroy'))->assertForbidden();
        $this->put(route('user-password.update'))->assertForbidden();
        $this->post(route('two-factor.enable'))->assertForbidden();
        $this->post(route('passkey.store'))->assertForbidden();

        $user->refresh();
        $this->assertSame('Demo User', $user->name);
        $this->assertSame(config('demo.email'), $user->email);
        $this->assertNull($user->two_factor_secret);
    }

    public function test_regular_users_are_not_affected_by_the_demo_guard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('profile.update'), ['name' => 'New Name', 'email' => $user->email])
            ->assertRedirect();

        $this->assertSame('New Name', $user->refresh()->name);
    }

    public function test_the_demo_account_never_receives_emails(): void
    {
        Notification::fake();
        $user = app(ResetDemoAccount::class)->handle();

        $this->post(route('password.email'), ['email' => $user->email]);
        Notification::assertNotSentTo($user, ResetPassword::class);

        $reminder = Reminder::factory()
            ->for(JobApplication::factory()->for($user))
            ->due()
            ->create();

        $this->assertSame([], (new ReminderNotification($reminder))->via($user));

        (new SendReminder($reminder))->handle();
        $this->assertNotNull($reminder->refresh()->sent_at);
    }

    public function test_the_reset_command_rebuilds_the_board(): void
    {
        $user = app(ResetDemoAccount::class)->handle();
        $user->jobApplications()->delete();
        $user->jobApplications()->create(['company' => 'Left over', 'position' => 'Dev', 'status' => 'wishlist']);

        $this->artisan('demo:reset')->assertSuccessful();

        $this->assertSame(11, $user->jobApplications()->count());
        $this->assertFalse($user->jobApplications()->where('company', 'Left over')->exists());
    }

    public function test_the_if_stale_option_only_resets_after_the_configured_interval(): void
    {
        $user = app(ResetDemoAccount::class)->handle();
        $user->jobApplications()->delete();

        $this->artisan('demo:reset', ['--if-stale' => true])->assertSuccessful();
        $this->assertSame(0, $user->jobApplications()->count());

        $this->travel(25)->hours();

        $this->artisan('demo:reset', ['--if-stale' => true])->assertSuccessful();
        $this->assertSame(11, $user->jobApplications()->count());
    }

    public function test_the_cron_endpoint_resets_a_stale_demo_account(): void
    {
        config(['services.cron.secret' => 'top-secret']);

        $this->postJson(route('cron.run'), [], ['Authorization' => 'Bearer top-secret'])
            ->assertOk()
            ->assertJsonPath('demo', 'INFO  Demo account reset.');

        $this->assertSame(11, $this->demoUser()->jobApplications()->count());
    }

    public function test_the_reset_command_does_nothing_when_the_demo_is_disabled(): void
    {
        config(['demo.enabled' => false]);

        $this->artisan('demo:reset')->assertSuccessful();

        $this->assertSame(0, User::query()->where('is_demo', true)->count());
    }
}
