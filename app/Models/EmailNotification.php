<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailNotification extends Model
{
    protected $table = 'email_notifications';

    public $timestamps = false;

    protected $fillable = [
        'agreement_id',
        'user_id',
        'recipient_email',
        'recipient_code',
        'tipe_notif',
        'days_remaining',
        'subject',
        'body_html',
        'status_kirim',
        'keterangan_status',
        'is_read',
        'sent_at',
    ];
}
