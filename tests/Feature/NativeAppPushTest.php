<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use App\Models\WorkOrder;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\WorkOrderAssigned;
use Database\Seeders\StageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NativeAppPushTest extends TestCase
{
    use RefreshDatabase;

    private string $credentials;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(StageSeeder::class);
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        $this->credentials = tempnam(sys_get_temp_dir(), 'fcm');
        file_put_contents($this->credentials, json_encode([
            'client_email' => 'avisos@carpinteria.iam.gserviceaccount.com',
            'private_key' => $pem,
            'token_uri' => 'https://oauth2.googleapis.com/token',
        ]));
        config(['services.fcm.project_id' => 'carpinteria-test', 'services.fcm.credentials' => $this->credentials]);
        Cache::flush();
    }

    protected function tearDown(): void
    {
        @unlink($this->credentials);
        parent::tearDown();
    }

    public function test_the_app_registers_and_removes_a_phone(): void
    {
        $juan = User::factory()->carpintero()->create();

        $this->actingAs($juan)->postJson(route('push.device.store'), ['token' => 'tok-1', 'platform' => 'android', 'device_name' => 'Moto G'])
            ->assertNoContent();
        $this->assertDatabaseHas('device_tokens', ['user_id' => $juan->id, 'token' => 'tok-1', 'platform' => 'android']);

        $this->actingAs($juan)->postJson(route('push.device.store'), ['token' => 'tok-1', 'platform' => 'windows'])
            ->assertUnprocessable();

        $this->actingAs($juan)->deleteJson(route('push.device.destroy'), ['token' => 'tok-1'])->assertNoContent();
        $this->assertDatabaseCount('device_tokens', 0);
    }

    public function test_a_shared_phone_moves_to_whoever_logs_in(): void
    {
        $juan = User::factory()->carpintero()->create();
        $pedro = User::factory()->carpintero()->create();
        DeviceToken::create(['user_id' => $juan->id, 'token' => 'tok-1', 'platform' => 'ios']);

        $this->actingAs($pedro)->postJson(route('push.device.store'), ['token' => 'tok-1', 'platform' => 'ios'])->assertNoContent();

        $this->assertSame($pedro->id, DeviceToken::sole()->user_id);
    }

    public function test_logging_out_from_the_app_stops_the_notifications(): void
    {
        $juan = User::factory()->carpintero()->create();
        DeviceToken::create(['user_id' => $juan->id, 'token' => 'tok-1', 'platform' => 'android']);

        $this->actingAs($juan)->post(route('logout'), ['device_token' => 'tok-1'])->assertRedirect(route('login'));

        $this->assertDatabaseCount('device_tokens', 0);
    }

    public function test_notifications_go_to_each_phone_through_fcm(): void
    {
        Http::fake([
            'oauth2.googleapis.com/*' => Http::response(['access_token' => 'ya29.test', 'expires_in' => 3600]),
            'fcm.googleapis.com/*' => Http::sequence()
                ->push(['name' => 'projects/carpinteria-test/messages/1'])
                ->push(['error' => ['code' => 404, 'status' => 'NOT_FOUND', 'details' => [['errorCode' => 'UNREGISTERED']]]], 404),
        ]);
        $juan = User::factory()->carpintero()->create();
        DeviceToken::create(['user_id' => $juan->id, 'token' => 'tok-vigente', 'platform' => 'android']);
        DeviceToken::create(['user_id' => $juan->id, 'token' => 'tok-desinstalada', 'platform' => 'ios']);
        $order = WorkOrder::factory()->create();

        $notification = new WorkOrderAssigned($order);
        $this->assertContains(FcmChannel::class, $notification->via($juan));
        $juan->notify($notification);

        Http::assertSent(fn ($request) => $request->url() === 'https://fcm.googleapis.com/v1/projects/carpinteria-test/messages:send'
            && $request->hasHeader('Authorization', 'Bearer ya29.test')
            && $request['message']['token'] === 'tok-vigente'
            && str_contains($request['message']['notification']['title'], $order->code)
            && str_contains($request['message']['data']['url'], '/avisos/'));
        $this->assertNotNull(DeviceToken::firstWhere('token', 'tok-vigente')->last_used_at);
        $this->assertNull(DeviceToken::firstWhere('token', 'tok-desinstalada'), 'El token dado de baja en Firebase se borra.');
    }

    public function test_without_firebase_configured_the_channel_is_skipped(): void
    {
        config(['services.fcm.project_id' => null]);
        $juan = User::factory()->carpintero()->create();
        DeviceToken::create(['user_id' => $juan->id, 'token' => 'tok-1', 'platform' => 'android']);

        $this->assertNotContains(FcmChannel::class, (new WorkOrderAssigned(WorkOrder::factory()->create()))->via($juan));
    }
}
