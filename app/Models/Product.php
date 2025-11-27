<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $table = 'products';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'brand_id',
        'subcategory_id', 
        'barcode',
        'origin',
        'manufacture_country',
        'skin_type',
        'volume',
        'scent',
    ];


    /**
     * Quan hệ 1:N (Một sản phẩm có nhiều ảnh)
     * Liên kết với ProductImage qua khóa ngoại 'product_id'.
     */
    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    /**
     * Quan hệ N:1 (Nhiều Sản phẩm thuộc về Một Thương hiệu).
     * Liên kết với bảng brands thông qua khóa ngoại 'brand_id'.
     */
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    /**
     * Quan hệ N:1 (Nhiều Sản phẩm thuộc về Một Danh mục).
     * Liên kết với bảng categories thông qua khóa ngoại 'subcategory_id'.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'subcategory_id', 'id');
    }

    /**
     * Quan hệ 1:N: Product có thể có mặt trong nhiều mục Yêu thích (Favourites).
     */
    public function favourites(): HasMany
    {
        return $this->hasMany(Favourite::class, 'product_id', 'id');
    }
}