<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationRecipient extends Model
{
    protected $table = 'notification_recipients';

    protected $fillable = [
        'email',
        'label',
        'department',
        'formsubmit_token',
        'is_active',
        'is_verified',
        'last_error',
        'added_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'is_verified' => 'boolean',
        ];
    }
}
