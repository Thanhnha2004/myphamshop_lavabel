<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    public $timestamps = false;

    public const ROLE_USER = 1;
    public const ROLE_ADMIN = 2;

    protected $fillable = [
        'username',
        'name',
        'fullname',
        'email',
        'password',
        'email_verified_at',
        'role',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Quan hệ: Lấy các đơn hàng của người dùng này.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    /**
     * Quan hệ: Lấy danh sách sản phẩm yêu thích (Many-to-Many).
     */
    public function favourites()
    {
        // belongsToMany liên kết User với Product qua bảng trung gian 'favourites'
        return $this->belongsToMany(Product::class, 'favourites', 'user_id', 'product_id');
    }

    /**
     * Kiểm tra xem người dùng có phải là Admin không
     */
    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

}
