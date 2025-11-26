<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;
    
    protected $table = 'categories'; 

    public $timestamps = false; 

    protected $fillable = [
        'name',
        'description',
    ];


    /**
     * Quan hệ 1:N (Một Danh mục có nhiều Sản phẩm)
     * * Khóa ngoại trong bảng products là 'subcategory_id'
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'subcategory_id', 'id');
    }
}