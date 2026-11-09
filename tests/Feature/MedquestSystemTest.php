<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedquestSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_and_register_pages_render_cleanly(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Masuk ke Sistem');
        $response->assertSee('Buat Akun Baru');

        $regResponse = $this->get('/register');
        $regResponse->assertStatus(200);
        $regResponse->assertSee('PENDAFTARAN AKUN USER MEDQUEST');
    }

    public function test_superadmin_login_and_all_dashboard_pages_work(): void
    {
        $loginResp = $this->post('/login', [
            'identitas' => 'FJR',
            'password'  => 'medquest123',
        ]);
        $loginResp->assertRedirect();

        $agrResp = $this->get('/agreements');
        $agrResp->assertStatus(200);
        $agrResp->assertSee('UDD PMI Kota Surabaya');

        $sortResp = $this->get('/agreements?sort=newest&doc_type=ALL');
        $sortResp->assertStatus(200);

        $createResp = $this->get('/create');
        $createResp->assertStatus(200);

        $notifResp = $this->get('/notifications');
        $notifResp->assertStatus(200);

        $smtpResp = $this->get('/smtp-settings');
        $smtpResp->assertStatus(200);

        $approvalsResp = $this->get('/user-approvals');
        $approvalsResp->assertStatus(200);
    }

    public function test_artisan_deadline_notification_command_runs(): void
    {
        $this->artisan('medquest:notify-deadlines')->assertExitCode(0);
    }
}
