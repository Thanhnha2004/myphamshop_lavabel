<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favourite extends Model
{
    use HasFactory;

    // Tên bảng (nếu không theo quy tắc số nhiều mặc định)
    protected $table = 'favourites'; 

    // Các trường có thể được gán giá trị hàng loạt (Mass Assignable)
    protected $fillable = [
        'user_id',
        'product_id',
    ];

    /**
     * Quan hệ: Mục yêu thích thuộc về một User.
     */
    public function user()
    {
        // belongsTo(RelatedModel::class, 'foreign_key_on_this_model', 'owner_key_on_related_model')
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Quan hệ: Mục yêu thích thuộc về một Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}