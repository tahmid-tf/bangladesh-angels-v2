<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BanWealthOrder extends Model
{
    use HasFactory;

    public const STATUSES = ['pending', 'accepted', 'rejected'];

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'nav_at_order' => 'decimal:4',
            'estimated_units' => 'decimal:4',
            'date_of_birth' => 'date',
            'shariah' => 'boolean',
            'monthly' => 'boolean',
            'politically_exposed' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function investor()
    {
        return $this->belongsTo(User::class, 'investor_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
