<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HugpongBanayLeaderHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'hugpong_banay_id',
        'member_id',
        'started_at',
        'ended_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'date',
            'ended_at' => 'date',
        ];
    }

    public function hugpongBanay(): BelongsTo
    {
        return $this->belongsTo(HugpongBanay::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
