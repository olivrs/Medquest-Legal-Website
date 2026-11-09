<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'nama_lengkap',
        'kode_singkatan',
        'email',
        'password',
        'departemen_default',
        'allowed_departments',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function isSuperadmin(): bool
    {
        return in_array(strtolower((string) $this->role), ['superadmin', 'admin'], true);
    }
}
