<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'kot_number',
        'status',
        'sent_to_kitchen_at',
        'completed_at',
    ];

    protected $casts = [
        'sent_to_kitchen_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(KitchenOrderItem::class);
    }
}
