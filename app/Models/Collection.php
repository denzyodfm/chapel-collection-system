<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use HasFactory, SoftDeletes;

    public const BALIK_GASA = 'balik_gasa';

    public const DONATION = 'donation';

    public const HALAD = 'halad';

    public const TYPES = [
        self::BALIK_GASA => 'Balik Gasa',
        self::DONATION => 'Donation',
        self::HALAD => 'Offering',
    ];

    protected $fillable = [
        'member_id',
        'collection_type',
        'amount',
        'collection_date',
        'collection_month',
        'reference_no',
        'remarks',
        'encoded_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'collection_date' => 'date',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function encoder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->collection_type] ?? $this->collection_type;
    }
}
