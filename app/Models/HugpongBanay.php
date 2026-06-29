<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HugpongBanay extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'current_leader_id',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function currentLeader(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'current_leader_id');
    }

    public function leaderHistories(): HasMany
    {
        return $this->hasMany(HugpongBanayLeaderHistory::class);
    }

    public function activeLeaderHistory(): HasOne
    {
        return $this->hasOne(HugpongBanayLeaderHistory::class)->whereNull('ended_at');
    }
}
