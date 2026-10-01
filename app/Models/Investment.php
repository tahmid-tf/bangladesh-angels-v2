<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Deal;

class Investment extends Model
{
    protected $fillable = [
        'deal_id',
        'user_id',
        'type',
        'viewed_by',
        'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function viewer()
    {
        return $this->belongsTo(User::class, 'viewed_by');
    }

    public function deal()
    {
        return $this->belongsTo(Deal::class, 'deal_id');
    }

    public function isViewed(): bool
    {
        return $this->viewed_at !== null;
    }

    public function markAsViewed(?int $adminId = null): void
    {
        $this->update([
            'viewed_at' => now(),
            'viewed_by' => $adminId ?? auth()->id(),
        ]);
    }

    public function markAsUnviewed(): void
    {
        $this->update([
            'viewed_at' => null,
            'viewed_by' => null,
        ]);
    }
}
