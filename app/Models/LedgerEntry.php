<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LedgerEntry extends Model
{
    use HasFactory, SoftDeletes;

    public const CREDIT = 'credit';

    public const DEBIT = 'debit';

    protected $fillable = [
        'fund_type',
        'entry_type',
        'amount',
        'entry_date',
        'reference_no',
        'remarks',
        'encoded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'entry_date' => 'date',
        ];
    }

    public function encoder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }
}
