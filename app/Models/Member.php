<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'full_name',
        'contact_number',
        'address_purok',
        'hugpong_banay_id',
        'status',
        'date_joined',
    ];

    protected function casts(): array
    {
        return [
            'date_joined' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member): void {
            if ($member->member_id) {
                return;
            }

            $latestId = self::query()
                ->where('member_id', 'like', 'PHFC-%')
                ->orderByDesc('id')
                ->value('member_id');

            $nextNumber = 1;
            if ($latestId && preg_match('/PHFC-(\d+)/', $latestId, $matches)) {
                $nextNumber = (int) $matches[1] + 1;
            }

            do {
                $candidate = 'PHFC-'.str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
                $nextNumber++;
            } while (self::where('member_id', $candidate)->exists());

            $member->member_id = $candidate;
        });
    }

    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class);
    }

    public function hugpongBanay(): BelongsTo
    {
        return $this->belongsTo(HugpongBanay::class);
    }

    public function leaderHistories(): HasMany
    {
        return $this->hasMany(HugpongBanayLeaderHistory::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
