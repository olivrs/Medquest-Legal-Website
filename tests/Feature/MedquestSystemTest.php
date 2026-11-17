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
        $response->assertSee('Sign In');
        $response->assertSee('Create New Account');

        $regResponse = $this->get('/register');
        $regResponse->assertStatus(200);
        $regResponse->assertSee('MEDQUEST USER REGISTRATION');
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
        $agrResp->assertSee('INTERNAL MENU');
        $agrResp->assertSee('Departments');
        $agrResp->assertSee('Submission');
        $agrResp->assertSee('In Progress');
        $agrResp->assertSee('Completed');
        $agrResp->assertDontSee('STATUS ALUR PROSES');

        $sortResp = $this->get('/agreements?sort=newest&doc_type=ALL');
        $sortResp->assertStatus(200);
        $sortResp->assertDontSee('Dokumen / Customer');
        $sortResp->assertDontSee('Detail (Product / Issued By / Principal)');
        $sortResp->assertSee('<th style="width:240px;">Document No</th>', false);
        $sortResp->assertSee('<th style="width:200px;">Detail</th>', false);

        $createResp = $this->get('/create');
        $createResp->assertStatus(200);
        $createResp->assertSee('Add New Document');
        $createResp->assertDontSee('Status Alur Proses');
        $createResp->assertSee('Status <span class="text-danger">*</span>', false);

        $notifResp = $this->get('/notifications');
        $notifResp->assertStatus(200);
        $notifResp->assertSee('Deadline Monitoring');

        $smtpResp = $this->get('/smtp-settings');
        $smtpResp->assertStatus(200);
        $smtpResp->assertSee('Email SMTP Settings');

        $approvalsResp = $this->get('/user-approvals');
        $approvalsResp->assertStatus(200);
        $approvalsResp->assertSee('Account Approvals');
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

        $targetAgr = \App\Models\Agreement::where('customer', 'UDD PMI Kota Surabaya')->first();
        $this->assertNotNull($targetAgr);

        $this->post('/?page=agreements', [
            'action'        => 'update_process_status',
            'agreement_id'  => $targetAgr->id,
            'status_proses' => 'BERJALAN',
        ])->assertRedirect();

        $this->assertSame('BERJALAN', $targetAgr->fresh()->status_proses);

        $editResp = $this->post('/?page=edit&id=' . $targetAgr->id, [
            'department'          => $targetAgr->department,
            'status_proses'       => 'BERJALAN',
            'no_urut_dokumen'     => $targetAgr->no_urut_dokumen,
            'document_number'     => $targetAgr->document_number,
            'customer'            => $targetAgr->customer,
            'product_description' => $targetAgr->product_description,
            'serial_number'       => $targetAgr->serial_number,
            'expiry_date'         => $targetAgr->expiry_date,
            'note'                => $targetAgr->note,
            'created_by_log'      => $targetAgr->created_by_log,
        ]);
        $editResp->assertRedirect();
        $this->get($editResp->headers->get('Location'))->assertStatus(200);

        $this->post('/?page=detail&id=' . $targetAgr->id, [
            'action'          => 'update_process_status',
            'status_proses'   => 'SELESAI',
            'tanggal_selesai' => '2026-11-16',
            'catatan_status'  => 'Dokumen selesai diparaf',
        ])->assertRedirect();

        $this->assertSame('SELESAI', $targetAgr->fresh()->status_proses);
    }

    public function test_expiry_and_process_status_labels_are_fully_english(): void
    {
        $this->assertSame('EXPIRED (9d ago)', get_expiry_info(date('Y-m-d', strtotime('-9 days')))['short_label']);
        $this->assertSame('13 Days Left', get_expiry_info(date('Y-m-d', strtotime('+13 days')))['short_label']);
        $this->assertSame('Active (358 days)', get_expiry_info(date('Y-m-d', strtotime('+358 days')))['short_label']);
        $this->assertSame('Submission', get_process_status_meta('PENGAJUAN')['label']);
        $this->assertSame('In Progress', get_process_status_meta('BERJALAN')['label']);
        $this->assertSame('Completed', get_process_status_meta('SELESAI')['label']);

        $superadmin = \App\Models\User::where('kode_singkatan', 'FJR')->first();
        $this->assertNotNull($superadmin);
        $this->actingAs($superadmin);

        \App\Models\Agreement::create([
            'doc_type'            => 'COOPERATION',
            'no_urut_dokumen'     => 901,
            'department'          => 'LAC',
            'document_number'     => 'DOC-EN-901',
            'customer'            => 'English Label Expired Test',
            'product_description' => 'Test Product 1',
            'serial_number'       => 'N/A',
            'effective_date'      => date('Y-m-d', strtotime('-30 days')),
            'effective_date_na'   => false,
            'expiry_date'         => date('Y-m-d', strtotime('-9 days')),
            'expiry_date_na'      => false,
            'status_proses'       => 'BERJALAN',
            'note'                => 'N/A',
            'created_by_user_id'  => $superadmin->id,
            'created_by_code'     => 'FJR',
            'created_by_log'      => 'FJR, 17 NOV 2026',
        ]);

        \App\Models\Agreement::create([
            'doc_type'            => 'COOPERATION',
            'no_urut_dokumen'     => 902,
            'department'          => 'LAC',
            'document_number'     => 'DOC-EN-902',
            'customer'            => 'English Label Soon Test',
            'product_description' => 'Test Product 2',
            'serial_number'       => 'N/A',
            'effective_date'      => date('Y-m-d', strtotime('-10 days')),
            'effective_date_na'   => false,
            'expiry_date'         => date('Y-m-d', strtotime('+13 days')),
            'expiry_date_na'      => false,
            'status_proses'       => 'SELESAI',
            'tanggal_selesai'     => date('Y-m-d'),
            'note'                => 'N/A',
            'created_by_user_id'  => $superadmin->id,
            'created_by_code'     => 'FJR',
            'created_by_log'      => 'FJR, 17 NOV 2026',
        ]);

        $resp = $this->get('/agreements?dept=LAC&sort=newest');
        $resp->assertStatus(200);
        $resp->assertSee('EXPIRED (9d ago)');
        $resp->assertSee('13 Days Left');
        $resp->assertSee('In Progress');
        $resp->assertSee('Completed');
        $resp->assertDontSee('hr lalu');
        $resp->assertDontSee('Sisa ');
        $resp->assertDontSee('Aman (');
        $resp->assertDontSee('Sedang Berjalan');
        $resp->assertDontSee('Sudah Selesai');
    }
}
