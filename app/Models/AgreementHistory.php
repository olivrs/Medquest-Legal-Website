<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgreementHistory extends Model
{
    protected $table = 'agreement_histories';

    public $timestamps = false;

    protected $fillable = [
        'agreement_id',
        'user_id',
        'kode_singkatan',
        'tanggal_input',
        'catatan',
        'aksi',
        'created_at',
    ];
}
