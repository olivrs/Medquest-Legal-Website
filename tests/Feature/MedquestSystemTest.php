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

    public function test_superadmin_sees_all_documents_while_user_sees_only_own_and_department_documents(): void
    {
        $userGa = \App\Models\User::create([
            'nama_lengkap'       => 'Ryan GA',
            'kode_singkatan'     => 'RYN',
            'email'              => 'ryan.ga@medquest.co.id',
            'password'           => \Illuminate\Support\Facades\Hash::make('password123'),
            'departemen_default' => 'GA',
            'role'               => 'user',
            'status'             => 'approved',
        ]);

        $userMho = \App\Models\User::create([
            'nama_lengkap'       => 'Budi MHO',
            'kode_singkatan'     => 'BDI',
            'email'              => 'budi.mho@medquest.co.id',
            'password'           => \Illuminate\Support\Facades\Hash::make('password123'),
            'departemen_default' => 'MHO',
            'role'               => 'user',
            'status'             => 'approved',
        ]);

        \App\Models\Agreement::create([
            'doc_type'            => 'COOPERATION',
            'no_urut_dokumen'     => 801,
            'department'          => 'GA',
            'document_number'     => 'DOC-GA-801',
            'customer'            => 'Vendor Gedung Khusus GA',
            'product_description' => 'Sewa Operasional Gedung',
            'serial_number'       => 'N/A',
            'effective_date'      => '2026-01-01',
            'effective_date_na'   => false,
            'expiry_date'         => '2027-01-01',
            'expiry_date_na'      => false,
            'note'                => 'N/A',
            'created_by_user_id'  => $userGa->id,
            'created_by_code'     => 'RYN',
            'created_by_log'      => 'RYN, 09 NOV 2026',
        ]);

        \App\Models\Agreement::create([
            'doc_type'            => 'COOPERATION',
            'no_urut_dokumen'     => 802,
            'department'          => 'MHO',
            'document_number'     => 'DOC-MHO-802',
            'customer'            => 'RS Khusus Onkologi MHO',
            'product_description' => 'Alat Medis MHO',
            'serial_number'       => 'N/A',
            'effective_date'      => '2026-01-01',
            'effective_date_na'   => false,
            'expiry_date'         => '2027-01-01',
            'expiry_date_na'      => false,
            'note'                => 'N/A',
            'created_by_user_id'  => $userMho->id,
            'created_by_code'     => 'BDI',
            'created_by_log'      => 'BDI, 09 NOV 2026',
        ]);

        $this->actingAs($userGa);
        $respGa = $this->get('/agreements');
        $respGa->assertStatus(200);
        $respGa->assertSee('Vendor Gedung Khusus GA');
        $respGa->assertDontSee('RS Khusus Onkologi MHO');

        $this->actingAs($userMho);
        $respMho = $this->get('/agreements');
        $respMho->assertStatus(200);
        $respMho->assertSee('RS Khusus Onkologi MHO');
        $respMho->assertSee('RSUP Dr. Sardjito Yogyakarta');
        $respMho->assertDontSee('Vendor Gedung Khusus GA');

        $superadmin = \App\Models\User::where('kode_singkatan', 'FJR')->first();
        $this->actingAs($superadmin);
        $respSuper = $this->get('/agreements');
        $respSuper->assertStatus(200);
        $respSuper->assertSee('Vendor Gedung Khusus GA');
        $respSuper->assertSee('RS Khusus Onkologi MHO');
        $respSuper->assertSee('UDD PMI Kota Surabaya');

        $this->post('/?page=user_approvals', [
            'action'      => 'update_departments',
            'user_id'     => $userGa->id,
            'departments' => ['GA', 'MHO'],
        ])->assertRedirect();

        $this->actingAs($userGa->fresh());
        $respGaMulti = $this->get('/agreements');
        $respGaMulti->assertStatus(200);
        $respGaMulti->assertSee('Vendor Gedung Khusus GA');
        $respGaMulti->assertSee('RS Khusus Onkologi MHO');
        $respGaMulti->assertDontSee('UDD PMI Kota Surabaya');

        $this->actingAs($superadmin);
        $this->post('/?page=user_approvals', [
            'action'   => 'set_role',
            'user_id'  => $userGa->id,
            'new_role' => 'admin',
        ])->assertRedirect();

        $this->actingAs($userGa->fresh());
        $respGaAdmin = $this->get('/agreements');
        $respGaAdmin->assertStatus(200);
        $respGaAdmin->assertSee('Vendor Gedung Khusus GA');
        $respGaAdmin->assertSee('RS Khusus Onkologi MHO');
        $respGaAdmin->assertSee('UDD PMI Kota Surabaya');

        $idsToDelete = \App\Models\Agreement::whereIn('document_number', ['DOC-GA-801', 'DOC-MHO-802'])->pluck('id')->all();
        $this->assertCount(2, $idsToDelete);

        $this->post('/?page=agreements', [
            'action'        => 'bulk_delete',
            'agreement_ids' => $idsToDelete,
        ])->assertRedirect();

        $respAfterBulkDelete = $this->get('/agreements');
        $respAfterBulkDelete->assertStatus(200);
        $respAfterBulkDelete->assertDontSee('Vendor Gedung Khusus GA');
        $respAfterBulkDelete->assertDontSee('RS Khusus Onkologi MHO');
        $respAfterBulkDelete->assertSee('UDD PMI Kota Surabaya');
    }
}
