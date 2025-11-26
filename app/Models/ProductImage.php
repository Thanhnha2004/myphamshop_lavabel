<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $table = 'productimage'; 
    
    public $timestamps = false; 

    protected $fillable = [
        'product_id',
        'url',
    ];
    

    /**
     * Quan hệ N:1 (Nhiều ảnh thuộc về Một Sản phẩm)
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}