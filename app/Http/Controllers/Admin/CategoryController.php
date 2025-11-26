<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * GET /admin/categories
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

    /**
     * POST /admin/categories
     * Thêm Category mới.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string', 
        ]);

        DB::beginTransaction();

        try {
            $category = Category::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Thêm loại sản phẩm thành công.',
                'data' => $category,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi thêm loại sản phẩm.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /admin/categories/{id}
     * Lấy chi tiết một Category
     */
    public function show($id)
    {
        // Tìm Category và tải Sản phẩm liên quan
        $category = Category::with('products')->findOrFail($id);
        
        return response()->json([
            'message' => 'Lấy chi tiết loại sản phẩm thành công.',
            'data' => $category,
        ], 200);
    }

    /**
     * PUT/PATCH /admin/categories/{id}
     * Cập nhật Category.
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($category->id),
            ],
            'description' => 'nullable|string', 
        ]);

        DB::beginTransaction();

        try {
            $category->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật loại sản phẩm thành công.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi cập nhật loại sản phẩm.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * DELETE /admin/categories/{id}
     * Xóa Category.
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        DB::beginTransaction();

        try {
            // Kiểm tra ràng buộc: Nếu có Sản phẩm liên quan, không cho phép xóa
            if ($category->products()->exists()) {
                 return response()->json([
                    'error' => 'Không thể xóa loại sản phẩm này vì có sản phẩm liên quan. Vui lòng chuyển sản phẩm sang loại khác trước.'
                ], 409);
            }

            // Xóa Category
            $category->delete();

            DB::commit();

            return response()->json([
                'message' => 'Xóa loại sản phẩm thành công.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi xóa loại sản phẩm.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}