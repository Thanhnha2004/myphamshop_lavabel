<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::query();

        if ($search = $request->query('search')) {
            $query->where('brand_name', 'LIKE', "%{$search}%");
        }

        $brands = $query->orderBy('id', 'DESC')->paginate(15);

        return response()->json([
            'message' => 'Lấy danh sách thương hiệu thành công.',
            'data' => $brands,
        ], 200);
    }

    public function store(Request $request)
    {
        $request->validate([
            'brand_name' => 'required|string|max:255|unique:brands,brand_name',
        ]);

        DB::beginTransaction();

        try {
            $brand = Brand::create([
                'brand_name' => $request->brand_name,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Thêm thương hiệu thành công.',
                'data' => $brand,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Lỗi server khi thêm thương hiệu.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);

        $request->validate([
            'brand_name' => 'required|string|max:255|unique:brands,brand_name,' . $brand->id,
        ]);

        DB::beginTransaction();

        try {
            $brand->update([
                'brand_name' => $request->brand_name,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật thương hiệu thành công.',
                'data' => $brand,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Lỗi server khi cập nhật thương hiệu.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);

        if ($brand->products()->exists()) {
            return response()->json([
                'error' => 'Không thể xóa vì thương hiệu này có sản phẩm đang sử dụng.',
            ], 409);
        }

        DB::beginTransaction();

        try {
            $brand->delete();
            DB::commit();

            return response()->json([
                'message' => 'Xóa thương hiệu thành công.',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Lỗi server khi xóa thương hiệu.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    public function edit($id)
    {
        $brand = Brand::find($id);

        if (!$brand) {
            return response()->json([
                'error' => 'Không tìm thấy thương hiệu.',
            ], 404);
        }

        return response()->json([
            'message' => 'Lấy chi tiết thương hiệu thành công.',
            'data' => $brand,
        ]);
    }
}
