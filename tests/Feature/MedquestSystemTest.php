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

    public function test_dd_mm_yyyy_dates_are_parsed_and_rendered_properly(): void
    {
        $this->assertSame('2026-11-25', parse_date_medquest('25/11/2026'));
        $this->assertSame('2026-11-25', parse_date_medquest('2026-11-25'));
        $this->assertNull(parse_date_medquest('31/02/2026'));
        $this->assertSame('25/11/2026', format_date_input_medquest('2026-11-25'));

        $superadmin = \App\Models\User::where('kode_singkatan', 'FJR')->first();
        $this->assertNotNull($superadmin);
        $this->actingAs($superadmin);

        $this->post('/create', [
            'doc_type'            => 'COOPERATION',
            'department'          => 'LAC',
            'document_number'     => 'DMY-001',
            'customer'            => 'Hospital DMY',
            'product_description' => 'DMY Device',
            'tanggal_input'       => '17/11/2026',
            'effective_date'      => '01/11/2026',
            'expiry_date'         => '25/12/2026',
            'status_proses'       => 'SELESAI',
            'tanggal_selesai'     => '18/11/2026',
        ])->assertRedirect();

        $agr = \App\Models\Agreement::where('document_number', 'DMY-001')->first();
        $this->assertNotNull($agr);
        $this->assertSame('2026-11-01', $agr->effective_date);
        $this->assertSame('2026-12-25', $agr->expiry_date);
        $this->assertSame('2026-11-18', $agr->tanggal_selesai);

        $editPage = $this->get('/edit/' . $agr->id);
        $editPage->assertStatus(200);
        $editPage->assertSee('value="01/11/2026"', false);
        $editPage->assertSee('value="25/12/2026"', false);
        $editPage->assertSee('value="18/11/2026"', false);
        $editPage->assertSee('placeholder="dd/mm/yyyy"', false);
    }

    public function test_export_documents_page_and_xlsx_download_match_table_layout_and_scope(): void
    {
        $superadmin = \App\Models\User::where('kode_singkatan', 'FJR')->first();
        $this->assertNotNull($superadmin);
        $this->actingAs($superadmin);

        \App\Models\Agreement::create([
            'doc_type'            => 'COOPERATION',
            'no_urut_dokumen'     => 910,
            'department'          => 'LAC',
            'document_number'     => 'EXP-LAC-910',
            'customer'            => 'RS Medquest Export Test',
            'product_description' => 'Ventilator Pro',
            'serial_number'       => 'SN-910',
            'effective_date'      => date('Y-m-d'),
            'effective_date_na'   => false,
            'expiry_date'         => date('Y-m-d', strtotime('+10 days')),
            'expiry_date_na'      => false,
            'status_proses'       => 'SELESAI',
            'tanggal_selesai'     => date('Y-m-d'),
            'note'                => 'Exported note row',
            'created_by_user_id'  => $superadmin->id,
            'created_by_code'     => 'FJR',
            'created_by_log'      => 'FJR, 23 NOV 2026',
        ]);

        \App\Models\Agreement::create([
            'doc_type'            => 'COOPERATION',
            'no_urut_dokumen'     => 911,
            'department'          => 'ITD',
            'document_number'     => 'EXP-ITD-911',
            'customer'            => 'ITD Secret Customer',
            'product_description' => 'Server Unit',
            'serial_number'       => 'SN-911',
            'effective_date'      => date('Y-m-d'),
            'effective_date_na'   => false,
            'expiry_date'         => date('Y-m-d', strtotime('+200 days')),
            'expiry_date_na'      => false,
            'status_proses'       => 'PENGAJUAN',
            'note'                => 'ITD note',
            'created_by_user_id'  => $superadmin->id,
            'created_by_code'     => 'FJR',
            'created_by_log'      => 'FJR, 23 NOV 2026',
        ]);

        $dash = $this->get('/agreements');
        $dash->assertStatus(200);
        $dash->assertSee('Export Documents');

        $todayDateMeta = strtoupper(date('d M Y'));
        $todayDocCode = date('dmy');

        \App\Models\Agreement::create([
            'doc_type'            => 'COMPANY',
            'no_urut_dokumen'     => 185,
            'department'          => 'LAC',
            'document_title'      => 'Akta Pendirian PT Medquest',
            'document_number'     => 'AHU-0185',
            'issued_by'           => 'Notaris Putranto',
            'customer'            => 'PT Medquest Jaya Global',
            'product_description' => 'Akta Pendirian PT Medquest',
            'serial_number'       => 'N/A',
            'effective_date'      => '2025-10-01',
            'effective_date_na'   => false,
            'expiry_date'         => '2031-10-01',
            'expiry_date_na'      => false,
            'status_proses'       => 'SELESAI',
            'tanggal_selesai'     => '2025-10-01',
            'note'                => 'Original company record',
            'created_by_user_id'  => $superadmin->id,
            'created_by_code'     => 'FJR',
            'created_by_log'      => 'FJR, 01 OCT 2025',
        ]);

        $exportAll = $this->get('/?page=export');
        $exportAll->assertStatus(200);
        $exportAll->assertSee('Export Excel (.xlsx)');
        $exportAll->assertSee('Export PDF (.pdf)');
        $exportAll->assertSee('id="btnToggleExportPreview"', false);
        $exportAll->assertSee('id="mqExportPreviewCollapse" class="collapse"', false);
        $exportAll->assertSee('Preview');
        $exportAll->assertSee('MJG-EXP-ALL-ALL-' . $todayDocCode);
        $exportAll->assertSee(': ' . $todayDateMeta);
        $exportAll->assertSee('MEDQUEST LEGAL DOCUMENTS LIST');
        $exportAll->assertSee('Document Title / Customer');
        $exportAll->assertSee('Detail');
        $exportAll->assertSee('Status');
        $exportAll->assertSee('Effective Date');
        $exportAll->assertSee('Expiry Date');
        $exportAll->assertSee('Created By');
        $exportAll->assertSee('RS Medquest Export Test');

        $exportPrincipal = $this->get('/export?doc_type=PRINCIPAL');
        $exportPrincipal->assertStatus(200);
        $exportPrincipal->assertSee('MJG-EXP-PRIN-ALL-' . $todayDocCode);
        $exportPrincipal->assertSee('LOG OF PRINCIPAL DOCUMENT');
        $exportPrincipal->assertSee('Document Title');
        $exportPrincipal->assertSee('Issued by');
        $exportPrincipal->assertSee('Principal');

        $exportCompany = $this->get('/export?doc_type=COMPANY&dept=LAC');
        $exportCompany->assertStatus(200);
        $exportCompany->assertSee('MJG-EXP-COMP-LAC-' . $todayDocCode);
        $exportCompany->assertSee('ORIGINAL COMPANY DOCUMENT');
        $exportCompany->assertSee('Document Title');
        $exportCompany->assertSee('Document No.');
        $exportCompany->assertSee('Issued by');
        $exportCompany->assertSee('185');
        $exportCompany->assertSee('Akta Pendirian PT Medquest');
        $exportCompany->assertSee('Notaris Putranto');

        $exportCoopSelesai = $this->get('/export?doc_type=COOPERATION&dept=LAC&proses=SELESAI');
        $exportCoopSelesai->assertStatus(200);
        $exportCoopSelesai->assertSee('MJG-EXP-COOP-LAC-ACC-' . $todayDocCode);
        $exportCoopSelesai->assertSee('LOG OF COOPERATION AGREEMENT');
        $exportCoopSelesai->assertSee('Department / Division');
        $exportCoopSelesai->assertSee('Document Number');
        $exportCoopSelesai->assertSee('Customer');
        $exportCoopSelesai->assertSee('Product');
        $exportCoopSelesai->assertSee('Description');
        $exportCoopSelesai->assertSee('Serial Number');
        $exportCoopSelesai->assertSee('910');
        $exportCoopSelesai->assertSee('RS Medquest Export Test');

        $exportDeadlines = $this->get('/export?dataset=deadlines&dept=LAC');
        $exportDeadlines->assertStatus(200);
        $exportDeadlines->assertSee('MJG-EXP-DL-LAC-' . $todayDocCode);
        $exportDeadlines->assertSee('DEADLINE MONITORING (H-90 TO H-0)');
        $exportDeadlines->assertSee('RS Medquest Export Test');
        $exportDeadlines->assertDontSee('ITD Secret Customer');

        $xlsxResp = $this->get('/?page=export&dept=LAC&doc_type=COOPERATION&proses=SELESAI&download=xlsx');
        $xlsxResp->assertStatus(200);
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $xlsxResp->headers->get('Content-Type')
        );
        $xlsxBytes = $xlsxResp->getContent();
        $this->assertTrue(str_starts_with($xlsxBytes, "PK\x03\x04"));
        $this->assertStringContainsString('xl/worksheets/sheet1.xml', $xlsxBytes);
        $this->assertStringContainsString('xl/styles.xml', $xlsxBytes);
        $this->assertStringContainsString('xl/media/image1.png', $xlsxBytes);
        $this->assertStringContainsString('xl/drawings/drawing1.xml', $xlsxBytes);

        $sheetXml = '';
        $pos = 0;
        while (substr($xlsxBytes, $pos, 4) === "PK\x03\x04") {
            $hdr = unpack('Vsig/vver/vflag/vmethod/vmtime/vmdate/Vcrc/Vcomp/Vuncomp/vnlen/velen', substr($xlsxBytes, $pos, 30));
            $nameStart = $pos + 30;
            $entryName = substr($xlsxBytes, $nameStart, $hdr['nlen']);
            $dataStart = $nameStart + $hdr['nlen'] + $hdr['elen'];
            $rawData = substr($xlsxBytes, $dataStart, $hdr['comp']);
            if ($entryName === 'xl/worksheets/sheet1.xml') {
                $sheetXml = $hdr['method'] === 8 ? (string) gzinflate($rawData) : $rawData;
                break;
            }
            $pos = $dataStart + $hdr['comp'];
        }
        $this->assertStringContainsString('LOG OF COOPERATION AGREEMENT', $sheetXml);
        $this->assertStringContainsString('Department / Division', $sheetXml);
        $this->assertStringContainsString('<mergeCell ref="D6:E6"/>', $sheetXml);
        $this->assertStringContainsString('<mergeCell ref="I1:J1"/>', $sheetXml);
        $this->assertStringContainsString('<autoFilter ref="A7:J', $sheetXml);
        $this->assertStringContainsString('Serial Number', $sheetXml);

        $regularUser = \App\Models\User::create([
            'nama_lengkap'       => 'Regular Scope Tester',
            'kode_singkatan'     => 'RST',
            'email'              => 'rst@medquest.co.id',
            'password'           => bcrypt('password'),
            'departemen_default' => 'LAC',
            'role'               => 'user',
            'status'             => 'approved',
        ]);
        $this->actingAs($regularUser);

        $scopedExport = $this->get('/export');
        $scopedExport->assertStatus(200);
        $scopedExport->assertSee('RS Medquest Export Test');
        $scopedExport->assertDontSee('ITD Secret Customer');

        $this->actingAs($superadmin);

        \App\Models\Agreement::create([
            'doc_type'            => 'COOPERATION',
            'no_urut_dokumen'     => 912,
            'department'          => 'LAC',
            'document_number'     => 'EXP-RED-912',
            'customer'            => 'Overdue Doc Test',
            'product_description' => 'Overdue Item',
            'serial_number'       => 'N/A',
            'effective_date'      => '2026-09-01',
            'effective_date_na'   => false,
            'expiry_date'         => date('Y-m-d', strtotime('-5 days')),
            'expiry_date_na'      => false,
            'status_proses'       => 'PENGAJUAN',
            'note'                => 'Overdue row',
            'created_by_user_id'  => $superadmin->id,
            'created_by_code'     => 'FJR',
            'created_by_log'      => 'FJR, 23 NOV 2026',
        ]);

        \App\Models\Agreement::create([
            'doc_type'            => 'COOPERATION',
            'no_urut_dokumen'     => 913,
            'department'          => 'LAC',
            'document_number'     => 'EXP-YEL-913',
            'customer'            => 'Near Deadline Doc Test',
            'product_description' => 'Near Item',
            'serial_number'       => 'N/A',
            'effective_date'      => '2026-09-15',
            'effective_date_na'   => false,
            'expiry_date'         => date('Y-m-d', strtotime('+5 days')),
            'expiry_date_na'      => false,
            'status_proses'       => 'BERJALAN',
            'note'                => 'Near deadline row',
            'created_by_user_id'  => $superadmin->id,
            'created_by_code'     => 'FJR',
            'created_by_log'      => 'FJR, 23 NOV 2026',
        ]);

        $rangeResp = $this->get('/export?dept=LAC&date_field=effective_date&date_from=01/09/2026&date_to=10/09/2026&sort=deadline_asc');
        $rangeResp->assertStatus(200);
        $rangeResp->assertSee('Overdue Doc Test');
        $rangeResp->assertDontSee('Near Deadline Doc Test');

        $colorResp = $this->get('/export?dept=LAC&doc_type=COOPERATION&sort=deadline_asc');
        $colorResp->assertStatus(200);
        $colorResp->assertSee('mq-row-approved');
        $colorResp->assertSee('mq-row-expired');
        $colorResp->assertSee('mq-row-warning');

        $dashColorResp = $this->get('/agreements');
        $dashColorResp->assertStatus(200);
        $dashColorResp->assertSee('class="row-completed"', false);
        $dashColorResp->assertSee('class="row-expired"', false);
        $dashColorResp->assertSee('class="row-warning"', false);
        $dashColorResp->assertSee('class="row-active"', false);
    }

    public function test_notification_recipients_hari_h_and_smtp_gateway_work(): void
    {
        $this->assertSame([90, 60, 30, 14, 7, 3, 0], DEADLINE_MILESTONES);
        $this->assertSame('H-0', \App\Services\NotificationService::resolveDueMilestoneCode(0));
        $this->assertSame('H-3', \App\Services\NotificationService::resolveDueMilestoneCode(2));
        $this->assertSame('H-90', \App\Services\NotificationService::resolveDueMilestoneCode(90));
        $this->assertNull(\App\Services\NotificationService::resolveDueMilestoneCode(-1));

        $superadmin = \App\Models\User::where('kode_singkatan', 'FJR')->first();
        $this->assertNotNull($superadmin);
        $this->actingAs($superadmin);

        \App\Models\Agreement::where('expiry_date_na', 0)->update(['status_proses' => 'SELESAI']);
        \App\Models\EmailNotification::query()->delete();

        $agrToday = \App\Models\Agreement::create([
            'doc_type'            => 'COOPERATION',
            'no_urut_dokumen'     => 950,
            'department'          => 'LAC',
            'document_number'     => 'H0-TEST-950',
            'customer'            => 'Hospital Hari H Due',
            'product_description' => 'Critical Analyzer',
            'serial_number'       => 'SN-H0',
            'effective_date'      => date('Y-m-d', strtotime('-1 year')),
            'effective_date_na'   => false,
            'expiry_date'         => date('Y-m-d'),
            'expiry_date_na'      => false,
            'status_proses'       => 'BERJALAN',
            'note'                => 'Hari H check',
            'created_by_user_id'  => $superadmin->id,
            'created_by_code'     => 'FJR',
            'created_by_log'      => 'FJR, 24 NOV 2026',
        ]);

        $agrCompleted = \App\Models\Agreement::create([
            'doc_type'            => 'COOPERATION',
            'no_urut_dokumen'     => 951,
            'department'          => 'LAC',
            'document_number'     => 'H0-DONE-951',
            'customer'            => 'Hospital Already Completed',
            'product_description' => 'Done Analyzer',
            'serial_number'       => 'SN-DONE',
            'effective_date'      => date('Y-m-d', strtotime('-1 year')),
            'effective_date_na'   => false,
            'expiry_date'         => date('Y-m-d'),
            'expiry_date_na'      => false,
            'status_proses'       => 'SELESAI',
            'tanggal_selesai'     => date('Y-m-d'),
            'note'                => 'Already done',
            'created_by_user_id'  => $superadmin->id,
            'created_by_code'     => 'FJR',
            'created_by_log'      => 'FJR, 24 NOV 2026',
        ]);

        $addResp1 = $this->post('/notifications', [
            'action'     => 'add_recipient',
            'email'      => 'legal.pic1@medquest.co.id',
            'label'      => 'Legal Officer 1',
            'department' => 'LAC',
        ]);
        $addResp1->assertRedirect();

        $this->assertDatabaseHas('notification_recipients', [
            'email'      => 'legal.pic1@medquest.co.id',
            'department' => 'LAC',
            'is_active'  => 1,
        ]);

        $this->assertNotNull($agrToday->fresh()->notif_h0_sent_at);
        $this->assertNull($agrCompleted->fresh()->notif_h0_sent_at);

        $this->assertSame(1, \App\Models\EmailNotification::where('agreement_id', $agrToday->id)
            ->where('tipe_notif', 'H-0')
            ->where('recipient_email', 'legal.pic1@medquest.co.id')
            ->count());
        $this->assertSame(1, \App\Models\EmailNotification::where('agreement_id', $agrToday->id)
            ->where('tipe_notif', 'H-0')
            ->where('recipient_email', 'fjr.medquest@gmail.com')
            ->count());
        $this->assertSame(0, \App\Models\EmailNotification::where('agreement_id', $agrCompleted->id)->count());

        $addResp2 = $this->post('/notifications', [
            'action'     => 'add_recipient',
            'email'      => 'legal.pic2@medquest.co.id',
            'label'      => 'Legal Officer 2',
            'department' => 'ALL',
        ]);
        $addResp2->assertRedirect();

        $this->assertSame(1, \App\Models\EmailNotification::where('agreement_id', $agrToday->id)
            ->where('tipe_notif', 'H-0')
            ->where('recipient_email', 'legal.pic2@medquest.co.id')
            ->count());
        $this->assertSame(1, \App\Models\EmailNotification::where('agreement_id', $agrToday->id)
            ->where('tipe_notif', 'H-0')
            ->where('recipient_email', 'legal.pic1@medquest.co.id')
            ->count());

        $notifView = $this->get('/notifications');
        $notifView->assertStatus(200);
        $notifView->assertSee('HARI H (H-0)');
        $notifView->assertSee('Auto Reminder Email Recipients');
        $notifView->assertDontSee('Auto Reminder Email Recipients (H-90');
        $notifView->assertSee('legal.pic1@medquest.co.id');
        $notifView->assertSee('legal.pic2@medquest.co.id');
        $notifView->assertSee('value="delete_recipient"', false);
        $notifView->assertSee('value="clear_logs"', false);

        $pic2 = \App\Models\NotificationRecipient::where('email', 'legal.pic2@medquest.co.id')->first();
        $this->assertNotNull($pic2);
        $this->post('/notifications', [
            'action'       => 'delete_recipient',
            'recipient_id' => $pic2->id,
        ])->assertRedirect();
        $this->assertDatabaseMissing('notification_recipients', ['id' => $pic2->id]);

        $this->post('/notifications', [
            'action' => 'clear_logs',
        ])->assertRedirect();
        $this->assertSame(0, \App\Models\EmailNotification::count());

        $pendingUser = \App\Models\User::create([
            'nama_lengkap'       => 'Staff Baru LAC',
            'kode_singkatan'     => 'SBL',
            'email'              => 'staff.baru@medquest.co.id',
            'password'           => \Illuminate\Support\Facades\Hash::make('password123'),
            'departemen_default' => 'LAC',
            'role'               => 'user',
            'status'             => 'pending',
        ]);

        $this->post('/user-approvals', [
            'action'  => 'approve',
            'user_id' => $pendingUser->id,
        ])->assertRedirect();

        $this->assertSame('approved', $pendingUser->fresh()->status);
        $this->assertDatabaseHas('notification_recipients', [
            'email'      => 'staff.baru@medquest.co.id',
            'department' => 'LAC',
        ]);

        $this->post('/smtp-settings', [
            'action'          => 'save',
            'smtp_enabled'    => '1',
            'smtp_host'       => 'smtp.gmail.com',
            'smtp_port'       => '587',
            'smtp_encryption' => 'tls',
            'smtp_username'   => 'alert@medquest.co.id',
            'smtp_password'   => 'abcd efgh ijkl mnop',
            'sender_name'     => 'Medquest Alert',
            'sender_email'    => 'alert@medquest.co.id',
        ])->assertRedirect();

        $savedSmtp = \App\Models\SmtpSetting::find(1);
        $this->assertNotNull($savedSmtp);
        $this->assertSame(1, (int) $savedSmtp->smtp_enabled);
        $this->assertSame('abcdefghijklmnop', $savedSmtp->smtp_password);
    }
}
