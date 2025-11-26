<?php

// App/Models/Brand.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;

    protected $table = 'brands';

    public $timestamps = false;

    protected $fillable = [
        'brand_name',
    ];

    // Cấu hình quan hệ với Product (một thương hiệu có nhiều sản phẩm)
    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id', 'id');
    }

    public function logo()
    {
        return $this->hasOne(BrandImage::class, 'brand_id', 'id')->latestOfMany();
    }
}
