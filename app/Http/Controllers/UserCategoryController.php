<?php

namespace App\Http\Controllers;

use App\Models\Category;

class UserCategoryController extends Controller
{
    /**
     * GET /api/categories
     * Lấy danh sách tất cả các Category.
     */
    public function index()
    {
        $categories = Category::all();

        return response()->json([
            'message' => 'Lấy danh sách loại sản phẩm thành công.',
            'data' => $categories,
        ], 200);
    }

}