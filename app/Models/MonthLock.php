<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthLock extends Model
{
    public const DISBURSEMENT = 'disbursement';

    public const TYPES = [
        Collection::BALIK_GASA => 'Balik Gasa',
        Collection::DONATION => 'Donation',
        Collection::HALAD => 'Offering',
        self::DISBURSEMENT => 'Disbursement',
    ];

    protected $fillable = [
        'lockable_type',
        'month',
        'locked_by',
    ];

    public function locker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public static function isLocked(string $lockableType, string $month): bool
    {
        return static::query()
            ->where('lockable_type', $lockableType)
            ->where('month', $month)
            ->exists();
    }

    public static function lockedMessage(string $lockableType, string $month): string
    {
        $label = self::TYPES[$lockableType] ?? 'Records';

        return "{$label} records for {$month} are locked. Ask an admin to unlock the month before encoding changes.";
    }
}
