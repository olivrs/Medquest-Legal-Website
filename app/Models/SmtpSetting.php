<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmtpSetting extends Model
{
    protected $table = 'smtp_settings';

    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = [
        'id',
        'smtp_enabled',
        'smtp_host',
        'smtp_port',
        'smtp_encryption',
        'smtp_username',
        'smtp_password',
        'sender_name',
        'sender_email',
        'updated_at',
    ];
}
