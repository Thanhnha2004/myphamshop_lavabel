<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// Model này ánh xạ với bảng 'order_details'
class OrderDetail extends Model
{
    use HasFactory;

    protected $table = 'order_details'; 
    
    public $timestamps = false; 

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price', 
    ];

    /**
     * Quan hệ: Mặt hàng thuộc về Đơn hàng.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Quan hệ: Mặt hàng liên kết với Sản phẩm.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}