<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipOrder extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'customer_snapshot' => 'array', 'plan_snapshot' => 'array',
            'business_snapshot' => 'array', 'policy_snapshot' => 'array',
            'accepted_at' => 'datetime', 'client_accepted_at' => 'datetime',
            'delivered_at' => 'datetime', 'delivery_confirmed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function records()
    {
        return $this->hasMany(MembershipOrderRecord::class);
    }
}
