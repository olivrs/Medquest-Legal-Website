<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agreement extends Model
{
    protected $table = 'agreements';

    protected $fillable = [
        'doc_type',
        'no_urut_dokumen',
        'department',
        'document_title',
        'document_number',
        'issued_by',
        'principal_name',
        'customer',
        'product_description',
        'serial_number',
        'effective_date',
        'effective_date_na',
        'expiry_date',
        'expiry_date_na',
        'note',
        'file_attachment',
        'created_by_user_id',
        'created_by_code',
        'created_by_log',
        'notif_h90_sent_at',
        'notif_h60_sent_at',
        'notif_h30_sent_at',
        'notif_h14_sent_at',
        'notif_h7_sent_at',
        'notif_h3_sent_at',
        'notif_h1_sent_at',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(AgreementHistory::class, 'agreement_id')->orderBy('id', 'asc');
    }
}
