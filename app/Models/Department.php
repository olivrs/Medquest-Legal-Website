<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    protected $table = 'departments';

    public $timestamps = false;

    protected $fillable = [
        'kode_dept',
        'nama_dept',
        'deskripsi',
        'created_at',
    ];
}
