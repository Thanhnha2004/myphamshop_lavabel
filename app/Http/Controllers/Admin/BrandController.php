<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\BrandImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BrandController extends Controller
{
    /**
     * Lấy danh sách Thương hiệu (GET /admin/brands)
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

    /**
     * Thêm Thương hiệu mới (POST /admin/brands)
     */
    public function store(Request $request)
    {
        // 1. Validation
        $request->validate([
            'brand_name' => 'required|string|max:255|unique:brands,brand_name',
            // Thêm validation cho ảnh logo (nullable)
            'logo_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // max 2MB
        ]);

        DB::beginTransaction();

        try {
            // 2. Lưu Brand
            $brand = Brand::create([
                'brand_name' => $request->brand_name,
            ]);

            // 3. Xử lý Ảnh (Logo)
            if ($request->hasFile('logo_image')) {
                // Lưu file vào thư mục 'brands' trong 'public' disk
                $path = $request->file('logo_image')->store('brands', 'public');

                // Lưu đường dẫn vào bảng BrandImage
                BrandImage::create([
                    'brand_id' => $brand->id,
                    'url' => $path, // Lưu đường dẫn nội bộ
                ]);
            }

            DB::commit();

            // Tải lại logo trước khi trả về
            $brand->load('logo');

            return response()->json([
                'message' => 'Thêm thương hiệu thành công.',
                'data' => $brand
            ], 201); // 201 Created

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi thêm thương hiệu.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trả về chi tiết một Thương hiệu (GET /admin/brands/{id}/edit)
     */
    public function edit($id)
    {
        // Eager loading logo khi tìm kiếm chi tiết
        $brand = Brand::with('logo')->find($id);

        if (!$brand) {
            return response()->json([
                'error' => 'Không tìm thấy thương hiệu.',
            ], 404); // Trả về 404 Not Found nếu không tìm thấy
        }

        return response()->json([
            'message' => 'Lấy chi tiết thương hiệu thành công.',
            'data' => $brand,
        ]);
    }

    /**
     * Cập nhật Thương hiệu (PUT/PATCH /admin/brands/{id})
     */
    public function update(Request $request, $id)
    {
        // Dùng findOrFail để xử lý 404
        $brand = Brand::findOrFail($id);

        // 1. Validation
        $request->validate([
            'brand_name' => 'required|string|max:255|unique:brands,brand_name,' . $brand->id . ',id',
            // Thêm validation cho ảnh logo (nullable)
            'logo_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        DB::beginTransaction();

        try {
            // Cập nhật thông tin Brand
            $brand->update([
                'brand_name' => $request->brand_name,
            ]);

            // Xử lý Ảnh
            if ($request->hasFile('logo_image')) {
                // Xóa ảnh cũ nếu tồn tại
                if ($oldLogo = $brand->logo) {
                    // Xóa file vật lý
                    Storage::disk('public')->delete($oldLogo->url);
                    // Xóa record DB
                    $oldLogo->delete();
                }

                // Upload ảnh mới
                $path = $request->file('logo_image')->store('brands', 'public');

                // Lưu đường dẫn mới vào bảng BrandImage
                BrandImage::create([
                    'brand_id' => $brand->id,
                    'url' => $path,
                ]);
            }

            DB::commit();

            // Tải lại logo trước khi trả về response
            $brand->load('logo');

            return response()->json([
                'message' => 'Cập nhật thương hiệu thành công.',
                'data' => $brand,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Lỗi server khi cập nhật thương hiệu.',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Xóa Thương hiệu (DELETE /admin/brands/{id})
     */
    public function destroy($id)
    {
        $brand = Brand::findOrFail($id);

        // Kiểm tra ràng buộc sản phẩm
        if ($brand->products()->exists()) {
            return response()->json([
                'error' => 'Không thể xóa vì thương hiệu này có sản phẩm đang sử dụng.',
            ], 409);
        }

        DB::beginTransaction();

        try {
            // Kiểm tra xem logo có tồn tại không 
            if ($brand->logo) {
                // Xóa file vật lý. $brand->logo là đối tượng BrandImage.
                Storage::disk('public')->delete($brand->logo->url);

                // Xóa record trong bảng brand_images thông qua quan hệ logo()
                $brand->logo()->delete();
            }

            // Xóa Brand
            $brand->delete();

            DB::commit();

            return response()->json([
                'message' => 'Xóa thương hiệu thành công.',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Lỗi khi xóa thương hiệu ID {$id}: " . $e->getMessage());
            return response()->json([
                'error' => 'Lỗi server khi xóa thương hiệu.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}