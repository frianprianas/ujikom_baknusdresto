<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KitchenOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'kitchen_order_id',
        'order_item_id',
        'quantity',
        'status',
        'notes',
    ];

    public function kitchenOrder()
    {
        return $this->belongsTo(KitchenOrder::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }
}
