<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
    'shipping_service',
    'shipping_price',
    'shipping_deadline',
    'cep',
    'address',
    'address_number',
    'address_complement',
    'city',
    'state',
    'transaction_id',
    'payment_method',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
