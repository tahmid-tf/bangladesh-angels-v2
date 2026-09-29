<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipOrderRecord extends Model
{
    protected $guarded = ['id'];

    public function order()
    {
        return $this->belongsTo(MembershipOrder::class, 'membership_order_id');
    }
}
