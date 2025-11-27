<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;

class UserBrandController extends Controller
{
    /**
     * Lấy danh sách Thương hiệu (GET /api/brands)
     */
    public function index(Request $request)
    {
        $query = Brand::query();

        // Lấy tham số tìm kiếm từ request
        $search = $request->query('search');

        // Xử lý tìm kiếm (Lọc)
        if ($search) {
            // Tìm kiếm theo tên thương hiệu
            $query->where('brand_name', 'LIKE', "%{$search}%");
        }

        // Thêm eager loading (.with('logo')), sắp xếp, và phân trang
        $brands = $query->with('logo')->orderBy('id', 'DESC')->paginate(15);
        
        return response()->json([
            'message' => 'Lấy danh sách thương hiệu thành công.',
            'data' => $brands,
        ], 200);
    }

}