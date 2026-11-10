<?php

namespace Database\Seeders;

use App\Models\Agreement;
use App\Models\AgreementHistory;
use App\Models\Department;
use App\Models\SmtpSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['kode_dept' => 'MHO', 'nama_dept' => 'Medical Healthcare & Oncology (MHO)', 'deskripsi' => 'Divisi MHO'],
            ['kode_dept' => 'MBB', 'nama_dept' => 'Medical Blood Bank (MBB)', 'deskripsi' => 'Divisi MBB - Palang Merah & UDD'],
            ['kode_dept' => 'MPC', 'nama_dept' => 'Medical Primary Care / Corporate (MPC)', 'deskripsi' => 'Divisi MPC'],
            ['kode_dept' => 'RA',  'nama_dept' => 'Regulatory Affairs (RA)', 'deskripsi' => 'Divisi Regulatory Affairs'],
            ['kode_dept' => 'GA',  'nama_dept' => 'General Affairs (GA)', 'deskripsi' => 'Divisi General Affairs & Operasional'],
            ['kode_dept' => 'LAC', 'nama_dept' => 'Legal & Agreement Compliance (LAC)', 'deskripsi' => 'Divisi Legal & Compliance'],
            ['kode_dept' => 'ITD', 'nama_dept' => 'Information Technology Department (ITD)', 'deskripsi' => 'Divisi IT & Systems'],
        ];

        foreach ($departments as $dept) {
            Department::updateOrCreate(
                ['kode_dept' => $dept['kode_dept']],
                [
                    'nama_dept' => $dept['nama_dept'],
                    'deskripsi' => $dept['deskripsi'],
                ]
            );
        }

        SmtpSetting::updateOrCreate(
            ['id' => 1],
            [
                'smtp_enabled'    => 0,
                'smtp_host'       => 'smtp.gmail.com',
                'smtp_port'       => 587,
                'smtp_encryption' => 'tls',
                'smtp_username'   => '',
                'smtp_password'   => '',
                'sender_name'     => 'Medquest Agreement Alert (LAC)',
                'sender_email'    => 'noreply@medquest.co.id',
            ]
        );

        $fjr = User::updateOrCreate(
            ['kode_singkatan' => 'FJR'],
            [
                'nama_lengkap'       => 'Fajar (Admin LAC Medquest)',
                'email'              => 'fjr.medquest@gmail.com',
                'password'           => Hash::make('medquest123'),
                'departemen_default' => 'ALL',
                'role'               => 'superadmin',
                'status'             => 'approved',
            ]
        );

        if (Agreement::count() === 0) {
            $samples = [
                [
                    'doc_type'            => 'COOPERATION',
                    'no_urut_dokumen'     => 731,
                    'department'          => 'MBB',
                    'document_number'     => "041/SP-KSO/UDDP-PMI/X/2021\n012/MJG-PKS/MBB/X/2021",
                    'customer'            => 'UDD PMI Kota Surabaya',
                    'product_description' => 'Kerjasama Operasional Alat NAT & Chemiluminescence Screening Darah',
                    'serial_number'       => 'SN-MBB-99201',
                    'effective_date'      => '2021-10-21',
                    'effective_date_na'   => false,
                    'expiry_date'         => date('Y-m-d', strtotime('+14 days')),
                    'expiry_date_na'      => false,
                    'note'                => "21 OCT 2026 :\nProses review addendum perpanjangan kontrak tahunan oleh tim LAC & MBB.",
                    'created_by_log'      => 'FJR, 21 OCT 2026',
                ],
                [
                    'doc_type'            => 'COOPERATION',
                    'no_urut_dokumen'     => 732,
                    'department'          => 'MHO',
                    'document_number'     => '088/PKS/RSUP-SARDJITO/XI/2023',
                    'customer'            => 'RSUP Dr. Sardjito Yogyakarta',
                    'product_description' => 'Perjanjian Kerjasama Penyediaan Reagen Onkologi & Diagnostik Molekuler',
                    'serial_number'       => 'N/A',
                    'effective_date'      => '2023-11-01',
                    'effective_date_na'   => false,
                    'expiry_date'         => date('Y-m-d', strtotime('+60 days')),
                    'expiry_date_na'      => false,
                    'note'                => "01 NOV 2026 :\nDokumen kontrak asli tersimpan di lemari arsip LAC Rak B-02.",
                    'created_by_log'      => 'FJR, 01 NOV 2026',
                ],
                [
                    'doc_type'            => 'COMPANY',
                    'no_urut_dokumen'     => 101,
                    'department'          => 'LAC',
                    'document_title'      => 'Izin Distribusi Alat Kesehatan (IDAK) PT Medquest Jaya Global',
                    'document_number'     => 'FK.01.01/VI/1290-e/2024',
                    'issued_by'           => 'Kementerian Kesehatan Republik Indonesia',
                    'customer'            => 'Kementerian Kesehatan Republik Indonesia',
                    'product_description' => 'Izin Distribusi Alat Kesehatan (IDAK) PT Medquest Jaya Global',
                    'serial_number'       => 'N/A',
                    'effective_date'      => '2024-01-15',
                    'effective_date_na'   => false,
                    'expiry_date'         => '2029-01-15',
                    'expiry_date_na'      => false,
                    'note'                => "15 JAN 2024 :\nSertifikat IDAK berlaku 5 tahun.",
                    'created_by_log'      => 'FJR, 15 JAN 2024',
                ],
                [
                    'doc_type'            => 'PRINCIPAL',
                    'no_urut_dokumen'     => 205,
                    'department'          => 'RA',
                    'document_title'      => 'Letter of Authorization (LoA) Exclusive Distributor Indonesia',
                    'document_number'     => 'LOA-GRIFOLS-ID-2025-09',
                    'principal_name'      => 'Grifols Diagnostic Solutions Inc.',
                    'customer'            => 'Grifols Diagnostic Solutions Inc.',
                    'product_description' => 'Letter of Authorization (LoA) Exclusive Distributor Indonesia',
                    'serial_number'       => 'N/A',
                    'effective_date'      => '2025-01-01',
                    'effective_date_na'   => false,
                    'expiry_date'         => date('Y-m-d', strtotime('+30 days')),
                    'expiry_date_na'      => false,
                    'note'                => "10 JAN 2025 :\nLoA sudah dilegalisasi KBRI dan diunggah ke sistem e-Watch Kemenkes.",
                    'created_by_log'      => 'FJR, 10 JAN 2025',
                ],
            ];

            foreach ($samples as $item) {
                $agr = Agreement::create(array_merge($item, [
                    'created_by_user_id' => $fjr->id,
                    'created_by_code'    => 'FJR',
                ]));

                AgreementHistory::create([
                    'agreement_id'   => $agr->id,
                    'user_id'        => $fjr->id,
                    'kode_singkatan' => 'FJR',
                    'tanggal_input'  => date('Y-m-d'),
                    'catatan'        => $item['note'],
                    'aksi'           => 'CREATE',
                ]);
            }
        }
    }
}
