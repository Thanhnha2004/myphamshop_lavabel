<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public $timestamps = false;

    // Trạng thái đơn hàng 
    public const STATUS_PENDING = 1;        
    public const STATUS_PROCESSING = 2;   
    public const STATUS_SHIPPED = 3;         
    public const STATUS_DELIVERED = 4;    
    public const STATUS_CANCELLED = 5;      

    protected $fillable = [
        'code', 
        'user_id',
        'customer_name',
        'customer_phone',
        'customer_address', 
        'total_amount',
        'status', // Mặc định là pending
        'note', 
    ];

    /**
     * Quan hệ: Đơn hàng thuộc về User (người đặt hàng).
     */
    public function user()
    {
        return $this->belongsTo(User::class); 
    }

    /**
     * Quan hệ: Lấy các mặt hàng trong đơn hàng
     */
    public function items()
    {
        return $this->hasMany(OrderDetail::class);
    }
}